#!/usr/bin/env python3
"""
Avalon CTF - architecture health & security check
-------------------------------------------------
Runs the platform checks from the testing plan in one command and prints
PASS / FAIL / WARN / SKIP for each. Standard library only (Python 3.8+).

Usage examples
  python avalon_check.py
  python avalon_check.py --vm-host 20.1.2.3
  python avalon_check.py --vm-host 20.1.2.3 --user demo_user --password 'Demo1234'
  python avalon_check.py --report report.txt

ONLY run this against your own platform and your own VM (authorised scope).
"""
import argparse
import http.cookiejar
import json
import re
import shutil
import socket
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

# ----------------------------- settings -----------------------------------
DEFAULTS = {
    "url": "http://localhost:8080",        # platform URL
    "web_container": "avalon_web",         # names from docker-compose.yml
    "db_container": "avalon_db",
    "db_port": 3306,
    "vm_host": "",                         # Azure VM IP/hostname (leave empty to skip VM checks)
    "vm_ssh_port": 22,
    "attachment_path": "/attachments/challenge1/avalon.png",
}
# Ports that must NOT be reachable on the VM (only SSH should be open).
VM_CLOSED_PORTS = [21, 23, 25, 80, 443, 445, 3306, 3389, 8080]
# ---------------------------------------------------------------------------

COLORS = {"PASS": "\033[92m", "FAIL": "\033[91m", "WARN": "\033[93m", "SKIP": "\033[90m"}
RESET = "\033[0m"
results = []


def record(status, name, detail=""):
    results.append((status, name, detail))
    color = COLORS[status] if sys.stdout.isatty() else ""
    end = RESET if sys.stdout.isatty() else ""
    print(f"[{color}{status}{end}] {name}" + (f"  - {detail}" if detail else ""))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


def request(opener, url, data=None, timeout=8):
    """Returns (status, headers, body). Never raises on HTTP error codes."""
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        r = opener.open(urllib.request.Request(url, data=body), timeout=timeout)
        return r.status, r.headers, r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode("utf-8", "replace")


def tcp_open(host, port, timeout=2.0):
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except OSError:
        return False


def lan_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("10.255.255.255", 1))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except OSError:
        return None


# ------------------------------- checks ------------------------------------
def check_docker(cfg):
    if not shutil.which("docker"):
        record("SKIP", "Docker containers", "docker CLI not found")
        return
    for name in (cfg.web_container, cfg.db_container):
        try:
            out = subprocess.run(
                ["docker", "inspect", "-f",
                 "{{.State.Status}}|{{if .State.Health}}{{.State.Health.Status}}{{end}}", name],
                capture_output=True, text=True, timeout=15)
        except (OSError, subprocess.TimeoutExpired):
            record("SKIP", f"Container {name}", "could not run docker")
            return
        if out.returncode != 0:
            record("FAIL", f"Container {name} running", "not found")
            continue
        state, _, health = out.stdout.strip().partition("|")
        ok = state == "running" and health in ("", "healthy")
        record("PASS" if ok else "FAIL", f"Container {name} running",
               state + (f", {health}" if health else ""))


def check_web(cfg):
    base = cfg.url.rstrip("/")
    plain = urllib.request.build_opener(NoRedirect)

    try:
        status, headers, _ = request(plain, base + "/login.php")
    except OSError as e:
        record("FAIL", "Platform reachable", f"{base} - {e}")
        return False
    record("PASS" if status == 200 else "FAIL", "Login page returns 200", f"HTTP {status}")

    record("PASS" if "X-Powered-By" not in headers else "FAIL",
           "PHP version not exposed (no X-Powered-By header)")

    cookies = headers.get_all("Set-Cookie") or []
    if cookies:
        ok = all("httponly" in c.lower() for c in cookies)
        record("PASS" if ok else "FAIL", "Session cookie is HttpOnly")
        ok = all("samesite" in c.lower() for c in cookies)
        record("PASS" if ok else "WARN", "Session cookie has SameSite")
    else:
        record("WARN", "Session cookie flags", "no cookie set by login page")

    status, _, _ = request(plain, base + cfg.attachment_path)
    record("PASS" if status in (403, 404) else "FAIL",
           "Direct access to challenge files blocked", f"HTTP {status} (expected 403)")

    for path, label in (("/index.php", "Dashboard"),
                        ("/challenge.php?id=1", "Challenge page"),
                        ("/download.php?aid=1", "File download")):
        status, headers, _ = request(plain, base + path)
        loc = headers.get("Location", "")
        ok = status in (301, 302, 303) and "login.php" in loc
        record("PASS" if ok else "FAIL", f"{label} requires login", f"HTTP {status} -> {loc or '-'}")

    status, _, _ = request(plain, base + "/submit_flag.php", data={"challenge_number": 1, "flag": "AVALON{x}"})
    record("PASS" if status == 401 else "FAIL", "Flag submission requires login", f"HTTP {status}")
    return True


def check_db_exposure(cfg):
    port = cfg.db_port
    if tcp_open("127.0.0.1", port):
        record("WARN", f"MySQL port {port} on localhost",
               "open on 127.0.0.1 (dev-only; remove the 'ports' line in docker-compose.yml to hide it)")
    else:
        record("PASS", f"MySQL port {port} not exposed on localhost")
    ip = lan_ip()
    if ip and not ip.startswith("127."):
        record("FAIL" if tcp_open(ip, port) else "PASS",
               f"MySQL port {port} not reachable from the network", f"tested {ip}")
    else:
        record("SKIP", "MySQL reachable from network", "no LAN address found")


def check_vm(cfg):
    host = cfg.vm_host
    if not host:
        record("SKIP", "Azure VM checks", "no --vm-host given")
        return
    ssh_ok = tcp_open(host, cfg.vm_ssh_port, 5)
    record("PASS" if ssh_ok else "FAIL", f"VM SSH port {cfg.vm_ssh_port} open")
    if not ssh_ok:
        record("SKIP", "VM exposes only SSH",
               "cannot verify: VM unreachable (check it is running, the IP, and the Azure NSG)")
        return
    open_ports = [p for p in VM_CLOSED_PORTS if p != cfg.vm_ssh_port and tcp_open(host, p, 2)]
    record("FAIL" if open_ports else "PASS", "VM exposes only SSH",
           f"unexpected open ports: {open_ports}" if open_ports else f"checked {len(VM_CLOSED_PORTS)} common ports")


def check_flag_endpoint(cfg):
    if not cfg.user:
        record("SKIP", "Flag validation tests", "no --user/--password given")
        return
    base = cfg.url.rstrip("/")
    jar = http.cookiejar.CookieJar()
    op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), NoRedirect)

    _, _, page = request(op, base + "/login.php")
    m = re.search(r'name="csrf_token" value="([^"]+)"', page)
    if not m:
        record("FAIL", "Flag tests: login form CSRF token found")
        return
    status, _, _ = request(op, base + "/login.php",
                           data={"csrf_token": m.group(1), "username": cfg.user, "password": cfg.password})
    if status not in (301, 302, 303):
        record("FAIL", "Flag tests: login as test user", f"HTTP {status} (check username/password)")
        return
    _, _, page = request(op, base + "/challenge.php?id=1")
    m = re.search(r'name="csrf-token" content="([^"]+)"', page)
    if not m:
        record("FAIL", "Flag tests: CSRF token found on challenge page")
        return
    token = m.group(1)

    def submit(flag, num=1, tok=token):
        s, _, b = request(op, base + "/submit_flag.php",
                          data={"csrf_token": tok, "challenge_number": num, "flag": flag})
        try:
            return s, json.loads(b)
        except ValueError:
            return s, {}

    # Each case waits >1s first so the throttle doesn't distort the result.
    cases = [
        ("wrong flag rejected", "AVALON{definitely_wrong}"),
        ("wrong format rejected", "not_a_flag"),
        ("SQL injection string rejected", "AVALON{' OR '1'='1}"),
        ("script content rejected", "AVALON{<script>alert(1)</script>}"),
        ("very long input rejected", "AVALON{" + "A" * 5000 + "}"),
    ]
    for label, flag in cases:
        time.sleep(1.2)
        s, j = submit(flag)
        record("PASS" if j.get("success") is False and s < 500 else "FAIL",
               f"Flag test: {label}", f"HTTP {s}")

    time.sleep(1.2)
    s, j = submit("AVALON{x}", tok="forged")
    record("PASS" if s == 403 else "FAIL", "Flag test: bad CSRF token rejected", f"HTTP {s}")

    time.sleep(1.2)
    s, j = submit("AVALON{x}", num=7)
    record("PASS" if s == 403 else "WARN", "Flag test: locked stage cannot be submitted",
           f"HTTP {s} (WARN if this user already unlocked stage 7)")

    time.sleep(1.2)
    submit("AVALON{x}")
    s, _ = submit("AVALON{x}")
    record("PASS" if s == 429 else "FAIL", "Flag test: rapid submissions throttled", f"HTTP {s} (expected 429)")


# -------------------------------- main -------------------------------------
def main():
    p = argparse.ArgumentParser(description="Avalon CTF architecture health & security check")
    p.add_argument("--url", default=DEFAULTS["url"])
    p.add_argument("--web-container", default=DEFAULTS["web_container"])
    p.add_argument("--db-container", default=DEFAULTS["db_container"])
    p.add_argument("--db-port", type=int, default=DEFAULTS["db_port"])
    p.add_argument("--vm-host", default=DEFAULTS["vm_host"])
    p.add_argument("--vm-ssh-port", type=int, default=DEFAULTS["vm_ssh_port"])
    p.add_argument("--attachment-path", default=DEFAULTS["attachment_path"])
    p.add_argument("--user", help="test account for the flag-validation tests (optional)")
    p.add_argument("--password", help="password for --user")
    p.add_argument("--report", help="also write the results to this text file")
    cfg = p.parse_args()

    print("=" * 60)
    print(" Avalon CTF - architecture health & security check")
    print(f" Platform: {cfg.url}   VM: {cfg.vm_host or 'not set'}")
    print("=" * 60)

    print("\n-- Containers --")
    check_docker(cfg)
    print("\n-- Web platform --")
    if check_web(cfg):
        print("\n-- Database exposure --")
        check_db_exposure(cfg)
        print("\n-- Flag validation --")
        check_flag_endpoint(cfg)
    print("\n-- Azure VM --")
    check_vm(cfg)

    counts = {s: sum(1 for r in results if r[0] == s) for s in ("PASS", "FAIL", "WARN", "SKIP")}
    summary = "  ".join(f"{k}: {v}" for k, v in counts.items())
    print("\n" + "=" * 60 + f"\n {summary}\n" + "=" * 60)

    if cfg.report:
        with open(cfg.report, "w", encoding="utf-8") as f:
            f.write(f"Avalon CTF check - {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            for s, n, d in results:
                f.write(f"[{s}] {n}" + (f" - {d}" if d else "") + "\n")
            f.write(summary + "\n")
        print(f" Report saved to {cfg.report}")
    sys.exit(1 if counts["FAIL"] else 0)


if __name__ == "__main__":
    main()
