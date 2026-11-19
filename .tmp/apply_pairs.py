import io, re

PATH = 'assets/css/app.css'

with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
    s = f.read()

norm = s.replace('\r\n', '\n')

pairs = eval(open('.tmp/pairs.py', encoding='utf-8').read())

applied, skipped = 0, []
for old, new in pairs:
    o = old.replace('\r\n', '\n')
    n = new.replace('\r\n', '\n')
    if o in norm:
        norm = norm.replace(o, n, 1)
        applied += 1
    elif n in norm:
        skipped.append(n.split('\n')[0][:50])
    else:
        print('MISSING:', repr(o[:80]))

with io.open(PATH, 'w', encoding='utf-8', newline='') as f:
    f.write(norm.replace('\n', '\r\n'))

print('applied', applied, 'already', len(skipped))
