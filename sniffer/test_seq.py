#!/usr/bin/env python3
"""Test reversed-order request AFTER basic visit on same connection."""
import socket, struct, json, sys, re, time
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake, build_visit_request
from test_full import build_reversed_request

def main():
    target = 776114101
    zone = 4280
    data = json.load(open('replay_data.json'))
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("need zstandard"); return

    print("=== Sequence: visit -> reversed request (same conn) ===")
    s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
    if not hresp:
        print("handshake failed"); return

    # 1. Basic visit first
    vreq = build_visit_request(target, zone, data['profile_request'])
    print(f"1. visit req ({len(vreq)}B)")
    s.sendall(vreq)
    resp1 = recv_all(s, timeout=10)
    print(f"   response: {len(resp1)} bytes")

    time.sleep(0.5)

    # 2. Reversed request
    rreq = build_reversed_request(target, zone)
    print(f"2. reversed req ({len(rreq)}B): {rreq.hex()}")
    s.sendall(rreq)
    resp2 = recv_all(s, timeout=15)
    s.close()
    print(f"   response: {len(resp2)} bytes")
    for typ, m in frame_msgs(resp2):
        if typ != 0x10:
            print(f"   type=0x{typ:02x} len={len(m)}: {m.hex()[:60]}")
            continue
        try:
            blob = dctx.decompress(m, max_output_size=50_000_000)
        except Exception as e:
            print(f"   type=0x10 decomp failed")
            continue
        print(f"   type=0x10 decomp={len(blob)}B")
        if len(blob) > 5000:
            print(f"   *** LARGE RESPONSE - possible full data! ***")
            open(f'full_seq_{target}.bin', 'wb').write(blob)
            # Quick check for skin-like data
            strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
            print(f"   strings: {len(strs)}")
            for x in strs[:8]:
                t = x.decode()
                if 'dist/' not in t and len(t) < 60:
                    print(f"     {t[:50]}")

if __name__ == '__main__':
    main()
