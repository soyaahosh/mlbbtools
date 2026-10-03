#!/usr/bin/env python3
"""Test visit+42 with CORRECT message ID (9f 4f) from pcap."""
import socket, struct, json, sys, re, time
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake, build_visit_request

# Correct template from pcap (t=12.52s)
# 70009f4f01c48c01450c7000b389fd7301b94842008080
TEMPLATE_9F4F = '70009f4f01c48c01450c7000b389fd7301b94842008080'

def build_9f4f_request(target_id, zone):
    raw = bytes.fromhex(TEMPLATE_9F4F)
    body = bytearray(raw)
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])

    # Inner: 70 00 <id> 01 <zone> 42 00 80
    # Template: 70 00 b389fd73 01 b948 42 00 80
    from mlbb_replay_v2 import encode_varint as ev
    j = inner.find(b'\x70\x00' + ev(243221683))
    assert j >= 0, "70 00 + id not found"
    k = j + 2
    e = k
    while inner[e] & 0x80: e += 1
    e += 1
    assert inner[e:e+1] == b'\x01'
    f = e + 1
    g = f
    while inner[g] & 0x80: g += 1
    g += 1
    # g now at 42 00
    new_inner = inner[:k] + encode_varint(target_id) + b'\x01' + encode_varint(zone) + inner[g:]
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

    # Verify builder
    req = build_9f4f_request(target, zone)
    print(f"Built req ({len(req)}B): {req.hex()}")
    assert '9f4f' in req.hex(), "missing 9f 4f msg ID!"
    print("Has 9f 4f msg ID: OK")

    print("\n=== visit -> 9f4f (same conn) ===")
    s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
    if not hresp:
        print("handshake failed"); return

    vreq = build_visit_request(target, zone, data['profile_request'])
    print(f"1. visit ({len(vreq)}B)")
    s.sendall(vreq)
    r1 = recv_all(s, timeout=8)
    print(f"   resp: {len(r1)}B")
    time.sleep(0.5)

    r9f = build_9f4f_request(target, zone)
    print(f"2. 9f4f ({len(r9f)}B)")
    s.sendall(r9f)
    r2 = recv_all(s, timeout=12)
    s.close()
    print(f"   resp: {len(r2)}B")

    for typ, m in frame_msgs(r2):
        if typ != 0x10:
            print(f"   type=0x{typ:02x} len={len(m)}")
            continue
        try:
            blob = dctx.decompress(m, max_output_size=50_000_000)
        except:
            continue
        print(f"   type=0x10 decomp={len(blob)}B")
        has_a04f = b'\xa0\x4f' in blob[:16]
        has_5f5c = b'\x5f\x5c' in blob
        print(f"   a0_4f header: {has_a04f}, skin tag 5f5c: {has_5f5c}")
        if len(blob) > 5000 and has_5f5c:
            print(f"   *** FULL DATA WITH SKINS! ***")
            open(f'full9f_{target}.bin', 'wb').write(blob)

if __name__ == '__main__':
    main()
