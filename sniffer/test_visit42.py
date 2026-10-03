#!/usr/bin/env python3
"""Test: visit -> visit+42 on SAME connection (triggers full data per pcap)."""
import socket, struct, json, sys, re, time
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake, build_visit_request
from test_variants import build_visit_variant

def main():
    target = 776114101
    zone = 4280
    data = json.load(open('replay_data.json'))
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("need zstandard"); return

    print("=== visit -> visit+42 (same conn) ===")
    s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
    if not hresp:
        print("handshake failed"); return

    # 1. Basic visit
    vreq = build_visit_request(target, zone, data['profile_request'])
    print(f"1. visit ({len(vreq)}B)")
    s.sendall(vreq)
    r1 = recv_all(s, timeout=8)
    print(f"   resp: {len(r1)}B")
    time.sleep(0.5)

    # 2. Visit + 42 flag (same connection!)
    v42 = build_visit_variant(target, zone, data['profile_request'], '4200')
    print(f"2. visit+42 ({len(v42)}B): {v42.hex()}")
    s.sendall(v42)
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
        if len(blob) > 5000:
            print(f"   *** FULL DATA! ***")
            # Check for skin-like patterns: look for timestamps (10-digit numbers as varints)
            # or just save and report
            open(f'full42_{target}.bin', 'wb').write(blob)
            strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
            print(f"   strings: {len(strs)}")
            # Count potential skin IDs (varints in 1000-3000 range are hard to count directly)
            # Just show first few strings
            for x in strs[:10]:
                t = x.decode()
                if 'dist/' not in t and len(t) < 60:
                    print(f"     {t[:50]}")
        else:
            strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
            for x in strs[:5]:
                t = x.decode()
                if 'dist/' not in t and len(t) < 50:
                    print(f"     {t[:40]}")

if __name__ == '__main__':
    main()
