print("generator ready")
BT=chr(96)
def row(c): return '| '+' | '.join(str(x) for x in c)+' |'
def hdr(): return '| TC-ID | Title | Priority | Severity | Preconditions | Steps | Expected Result |'
def sep(): return '|-------|-------|----------|----------|---------------|-------|-----------------|'
def bt(s): return BT+s+BT
L=[]
a=L.append
def S(t):
    a(''); a('---'); a(''); a('## '+t); a('')
def SS(t):
    a(''); a('### '+t); a(''); a(hdr()); a(sep())

a('# Employee Module - Test Case Document')
a('')
a('**Project:** Religious Affairs Management System (RAMS)')
a('**Module:** Employee | **Version:** 1.0.0 | **Date:** 2026-08-05')
a('**Author:** QA Team / RAMS Architect')
a('**Format:** Manual + Automated (PHPUnit Feature Tests)')
a(''); a('---'); a(''); a('## Legend'); a('')
a('| Symbol | Meaning |'); a('|--------|---------|')
a('| P1 | Priority 1 - Must pass before any release |')
a('| P2 | Priority 2 - Must pass before sprint close |')
a('| P3 | Priority 3 - Edge case coverage |')
a('| Critical | System crash, data loss, security breach |')
a('| High | Major feature broken, wrong data |')
a('| Medium | Minor feature broken, workaround exists |')
a('| Low | Cosmetic, UX issue |')
