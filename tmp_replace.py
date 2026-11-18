import re, pathlib
pat = re.compile(rb"upload_url\((.+?)\)\s*\?\?\s*asset\('images/default\.svg'\)")
for p in pathlib.Path("app/Views").rglob("*.php"):
    b = p.read_bytes()
    n = pat.sub(rb"avatar_url(\1)", b)
    if n != b:
        p.write_bytes(n)
        print(p)
