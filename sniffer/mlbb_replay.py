#!/usr/bin/env python3
"""
mlbb_replay.py — replay a captured MLBB profile-visit request.

Template: client_basicinfomatiion_visit (plaintext TCP protocol)
Server: 161.202.222.133:30103
Format : [00][3-byte BE len][70][00 a1][4e 01][XX 0e 45][7b 70 40]B12|<counter>;and_usa|<func>||9273|<tab>|<target>A[len]<ip>[02 c1 fe b2 e8 07]D[len]<func>[80 80]

Usage:
    python3 mlbb_replay.py <target_id> [tab] [b9]

  target_id : 9-digit profile ID, e.g. 243221683
  tab       : 1-7 (profile tabs), default 1
  b9        : byte[9] override as hex, default de (from capture)

Saves raw server response to resp_<target>_<tab>.bin for analysis.
"""
import socket
import sys
import time

SERVER = "161.202.222.133"
PORT = 30103
FUNC = b"client_basicinfomatiion_visit"

# Exact captured message (tab=1, target=243221683), used as template.
TEMPLATE = bytes.fromhex(
    "00000089"
    "7000a14e01de0e457b7040"
    "4231327c373034383337323b616e645f7573617c"
    "636c69656e745f6261736963696e666f6d617469696f6e5f7669736974"
    "7c7c393237337c317c323433323231363833"
    "410e32372e3132352e3234392e313239"
    "02c1feb2e807"
    "4417636c69656e745f6261736963696e666f6d617469696f6e5f7669736974"
    "8080"
)

# Offsets are located dynamically by searching for ASCII markers in TEMPLATE.
def _loc(marker: bytes, occurrence=1):
    idx = -1
    for _ in range(occurrence):
        idx = TEMPLATE.index(marker, idx + 1)
    return idx

OFF_B9 = 9  # unknown byte, varies per message (verified: differs across captures)
OFF_COUNTER = _loc(b"B12|") + 4          # 7 ASCII digits
OFF_TAB = _loc(b"9273|") + 5             # 1 ASCII digit
OFF_TARGET = _loc(b"243221683")          # 9 ASCII digits
assert TEMPLATE[OFF_B9] == 0xDE
assert TEMPLATE[OFF_COUNTER:OFF_COUNTER + 7] == b"7048372"
assert TEMPLATE[OFF_TAB:OFF_TAB + 1] == b"1"
assert TEMPLATE[OFF_TARGET:OFF_TARGET + 9] == b"243221683"


def build_visit(target_id, tab=1, b9=0xDE, counter=None, ip="27.125.249.129"):
    target_s = str(target_id)
    assert len(target_s) == 9, "target_id must be 9 digits (same length as captured)"
    if counter is None:
        counter = int(time.time() * 1000) % 10000000  # 7 digits
    counter_s = f"{counter:07d}"
    assert len(counter_s) == 7

    msg = bytearray(TEMPLATE)
    msg[OFF_B9] = b9
    msg[OFF_COUNTER:OFF_COUNTER + 7] = counter_s.encode()
    msg[OFF_TAB] = ord(str(tab))
    msg[OFF_TARGET:OFF_TARGET + 9] = target_s.encode()
    # fix total length prefix: 3-byte BE length INCLUDES the 4-byte header
    msg[1:4] = len(msg).to_bytes(3, "big")
    return bytes(msg)


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(1)
    target = sys.argv[1]
    tab = int(sys.argv[2]) if len(sys.argv) > 2 else 1
    b9 = int(sys.argv[3], 16) if len(sys.argv) > 3 else 0xDE

    pkt = build_visit(target, tab, b9)
    print(f"sending {len(pkt)} bytes: visit target={target} tab={tab} b9={b9:#04x}")
    print("hex:", pkt.hex()[:120], "...")

    s = socket.create_connection((SERVER, PORT), timeout=15)
    s.sendall(pkt)
    print("sent. waiting for response (10s)...")
    s.settimeout(10)
    chunks = []
    try:
        while True:
            d = s.recv(65536)
            if not d:
                break
            chunks.append(d)
            print(f"  got {len(d)} bytes")
            if len(b"".join(chunks)) > 100000:
                break
    except (socket.timeout, ConnectionResetError) as e:
        print(f"  (connection ended: {type(e).__name__})")
    finally:
        try:
            s.close()
        except Exception:
            pass

    raw = b"".join(chunks)
    fn = f"resp_{target}_{tab}.bin"
    open(fn, "wb").write(raw)
    print(f"total {len(raw)} bytes -> {fn}")
    if raw:
        # try to show structure: type + len + first bytes
        print("first 32 bytes:", raw[:32].hex())
        # check for any readable ascii
        txt = "".join(chr(b) if 32 <= b < 127 else "." for b in raw[:500])
        print("ascii preview:", txt[:200])


if __name__ == "__main__":
    main()
