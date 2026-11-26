#!/usr/bin/env python3
"""Fail when an OpenAPI Property description is outside 2-100 characters.

Mirrors the rule of the nightly "Schema Validation" workflow (Postman collection), which only sees the
deployed API one day after a merge. Only plain string literals are checked: a description built from
a variable or a call cannot be evaluated statically and is skipped.

Usage: check-schema-descriptions.py [path ...]   (default: src)
"""
import re
import sys
from pathlib import Path

MIN_LENGTH = 2
MAX_LENGTH = 100


def skip_string(src, i):
    """Return the index after the string literal that starts at src[i]."""
    quote = src[i]
    i += 1
    while i < len(src):
        if src[i] == '\\':
            i += 2
        elif src[i] == quote:
            return i + 1
        else:
            i += 1
    return i


def skip_comment(src, i):
    """Return the index after a comment starting at src[i], or i when there is none."""
    if src.startswith('//', i) or (src[i] == '#' and not src.startswith('#[', i)):
        end = src.find('\n', i)
        return len(src) if end == -1 else end
    if src.startswith('/*', i):
        end = src.find('*/', i + 2)
        return len(src) if end == -1 else end + 2
    return i


def call_arguments(src, open_paren):
    """Split the arguments of the call whose '(' is at open_paren; return them and the index after ')'."""
    args, depth, start, i = [], 0, open_paren + 1, open_paren
    while i < len(src):
        ch = src[i]
        if ch in '\'"':
            i = skip_string(src, i)
            continue
        end = skip_comment(src, i)
        if end != i:
            i = end
            continue
        if ch in '([{':
            depth += 1
        elif ch in ')]}':
            depth -= 1
            if depth == 0:
                args.append(src[start:i])
                return args, i + 1
        elif ch == ',' and depth == 1:
            args.append(src[start:i])
            start = i + 1
        i += 1
    return args, i


DOUBLE_QUOTED_TOKEN = re.compile(
    r'\\(?:[nrtvef\\$"]|[0-7]{1,3}|x[0-9A-Fa-f]{1,2}|u\{[0-9A-Fa-f]+\})'  # escape sequence: one character
    r'|(\$[A-Za-z_{]|\{\$)'  # interpolation: group 1, cannot be evaluated statically
    r'|.',
    re.S,
)


def double_quoted_length(body):
    """Length of a PHP double-quoted string body, or None when it interpolates."""
    length = 0
    for token in DOUBLE_QUOTED_TOKEN.finditer(body):
        if token.group(1):
            return None
        length += 1
    return length


def literal_value(expression):
    """Evaluate 'a' . "b" concatenations of plain string literals; None for anything dynamic."""
    parts, i, expression = [], 0, expression.strip()
    while i < len(expression):
        ch = expression[i]
        if ch.isspace() or ch == '.':
            i += 1
        elif ch in '\'"':
            end = skip_string(expression, i)
            body = expression[i + 1:end - 1]
            if ch == "'":
                parts.append(re.sub(r"\\([\\'])", r'\1', body))
            else:
                length = double_quoted_length(body)
                if length is None:
                    return None
                parts.append('x' * length)
            i = end
        else:
            return None
    return ''.join(parts) if parts else None


PROPERTY_CALL = re.compile(r'(?<![\w$>:])Property\s*\(')


def property_calls(src):
    """Yield the match of every Property( call in code, leaving out comments and string literals."""
    i = 0
    while i < len(src):
        end = skip_comment(src, i)
        if end != i:
            i = end
        elif src[i] in '\'"':
            i = skip_string(src, i)
        else:
            match = PROPERTY_CALL.match(src, i)
            if match:
                yield match
                i = match.end()
            else:
                i += 1


def check_file(path):
    src = path.read_text(encoding='utf-8')
    for match in property_calls(src):
        args, _ = call_arguments(src, match.end() - 1)
        for arg in args:
            named = re.match(r'\s*description\s*:(.*)$', arg, re.S)
            if not named:
                continue
            text = literal_value(named.group(1))
            if text is not None and not MIN_LENGTH <= len(text) <= MAX_LENGTH:
                line = src.count('\n', 0, match.start()) + 1
                yield line, len(text), text


def main(paths):
    failures = 0
    for root in map(Path, paths or ['src']):
        for path in sorted([root] if root.is_file() else root.rglob('*.php')):
            for line, length, text in check_file(path):
                failures += 1
                shown = text if len(text) <= 80 else text[:77] + '...'
                print(f'::error file={path},line={line}::Property description has {length} characters '
                      f'(allowed {MIN_LENGTH}-{MAX_LENGTH}): {shown}')
    if failures:
        print(f'{failures} property description(s) outside {MIN_LENGTH}-{MAX_LENGTH} characters')
    return 1 if failures else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
