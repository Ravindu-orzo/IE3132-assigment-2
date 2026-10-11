#!/usr/bin/env python3
"""
Usage:
    python3 test_all_flags.py --base http://localhost:8080 --user admin --password admin123
"""
import argparse
import re
import subprocess
import sys
import time

try:
    import requests
except ImportError:
    print("'requests' not found - installing...")
    subprocess.check_call([sys.executable, "-m", "pip", "install", "requests"])
    import requests

FLAGS = [
    "AVALON{tHe_D00R_Ha$_App3ArEd}",
    "AVALON{gr3pping_a1nt_th4t_h4rd_I$_1t}",
    "AVALON{N0_0n3_L3ft_Th3_T0w3r}",
    "AVALON{th4t_mu5t_h4v3_been_hard}",
    "AVALON{th4t_1s_why_y0u_3nabl3_http5}",
    "AVALON{w4tch_br3aking_b4d_1ts_g00d}",
    "AVALON{Av4l0n_W4s_N3v3r_F0rg0tt3n}",
]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", required=True)
    ap.add_argument("--user", required=True)
    ap.add_argument("--password", required=True)
    args = ap.parse_args()

    base = args.base.rstrip("/")
    s = requests.Session()

    # Login
    r = s.get(f"{base}/login.php")
    token = re.search(r'name="csrf_token" value="([^"]+)"', r.text).group(1)
    r = s.post(f"{base}/login.php",
               data={"csrf_token": token, "username": args.user, "password": args.password},
               allow_redirects=False)
    if r.status_code != 302:
        print("FAIL - login")
        return 1
    print("PASS - login")

    # CSRF token for submissions
    r = s.get(f"{base}/index.php")
    token = re.search(r'name="csrf-token" content="([^"]+)"', r.text).group(1)

    # Submit flags in order
    passed = 0
    for n, flag in enumerate(FLAGS, start=1):
        time.sleep(1.1)  # platform throttles rapid submissions
        resp = s.post(f"{base}/submit_flag.php",
                      data={"csrf_token": token, "challenge_number": n, "flag": flag}).json()
        ok = resp.get("success") is True
        passed += ok
        print(f"{'PASS' if ok else 'FAIL'} - stage {n}: {resp.get('message', '')}")

    print(f"{passed}/{len(FLAGS)} flags accepted")
    return 0 if passed == len(FLAGS) else 1


if __name__ == "__main__":
    sys.exit(main())
