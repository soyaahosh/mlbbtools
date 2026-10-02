#!/usr/bin/env python3
"""Test visit variants (42 00 / 4c 00 flags) to see if they return richer data."""
import socket, struct, json, sys, time, re
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake

def build_visit_variant(target_id, zone, template_hex, extra_hex):
    """Build visit with extra flag bytes (e.g. '4200' or '4c00').
    Uses build_visit_request for correct ID/zone, then inserts extra before final 80."""
    from mlbb_replay_v2 import build_visit_request
    base = bytearray(build_visit_request(target_id, zone, template_hex))
    # base ends with: ... 01 <zone varint> 80 80 ; insert extra before last 80 80
    assert base[-2:] == b'\x80\x80', f"unexpected tail: {base[-4:].hex()}"
    new = bytes(base[:-2]) + bytes.fromhex(extra_hex) + b'\x80\x80'
    # fixup frame length (byte 1-3)
    total = len(new)
    new = bytes([new[0], (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new[4:]
    # fixup inner 45 <len>
    j45 = new.find(b'\x45')
    inner_len = new[j45+1]
    new_inner_len = inner_len + len(bytes.fromhex(extra_hex))
    new = new[:j45+1] + bytes([new_inner_len]) + new[j45+2:]
    return new

def main():
    target = 776114101
    zone = 4280
    data = json.load(open('replay_data.json'))
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("need zstandard"); return

    for extra, label in [('4200', 'flag-42'), ('4c00', 'flag-4c')]:
        print(f"\n=== Testing {label} ({extra}) ===")
        s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
        if not hresp:
            print("handshake failed"); return
        vreq = build_visit_variant(target, zone, data['profile_request'], extra)
        print(f"req ({len(vreq)}B): {vreq.hex()}")
        s.sendall(vreq)
        resp = recv_all(s, timeout=10)
        s.close()
        print(f"response: {len(resp)} bytes")
        for typ, m in frame_msgs(resp):
            if typ != 0x10:
                continue
            try:
                blob = dctx.decompress(m, max_output_size=50_000_000)
            except Exception:
                continue
            print(f"  type=0x10 decomp={len(blob)}B")
            strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
            for x in strs[:15]:
                t = x.decode()
                if 'dist/' not in t:
                    print(f"    {t[:80]}")
            # save for analysis
            open(f'variant_{label}.bin', 'wb').write(blob)
            print(f"  saved variant_{label}.bin")

if __name__ == '__main__':
    main()
