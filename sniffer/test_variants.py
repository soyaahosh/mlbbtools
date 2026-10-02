#!/usr/bin/env python3
"""Test visit variants (42 00 / 4c 00 flags) to see if they return richer data."""
import socket, struct, json, sys, time, re
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake

def build_visit_variant(target_id, zone, template_hex, extra_hex):
    """Build visit with extra flag bytes (e.g. '4200' or '4c00')."""
    raw = bytes.fromhex(template_hex)
    body = bytearray(raw[4:])
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])
    # find 70 00 <id>, then 01 <zone>, then 80
    tid = encode_varint(target_id)
    j = inner.find(b'\x70\x00' + tid) + 2 + len(tid)
    assert inner[j:j+1] == b'\x01'
    k = j + 1
    e = k
    while inner[e] & 0x80:
        e += 1
    e += 1
    # e now points at 0x80 terminator; insert extra before it
    new_inner = inner[:e] + bytes.fromhex(extra_hex) + inner[e:]
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + bytes(new_inner) + trailer
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

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
