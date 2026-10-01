#!/usr/bin/env python3
"""Stage selected hunks of one file without an interactive `git add -p`.

Usage:
  stage_hunks.py list  <path> [--context N] [--brief]
  stage_hunks.py stage <path> <hunks> [--context N]

<hunks> is a comma list of indices or ranges from `list`, e.g. "0,2" or "1-3".
Hunks come from the unstaged diff (working tree vs index), so after staging
some hunks, run `list` again: the remaining hunks are renumbered.

A hunk that mixes two concerns can often be split with `--context 0`
(smaller hunks); use the same --context value for `list` and `stage`.
Untracked files must be marked first with `git add -N <path>`.
"""

import argparse
import subprocess
import sys


def file_diff(path: str, context: int) -> str:
    result = subprocess.run(
        ["git", "diff", f"-U{context}", "--no-color", "--no-ext-diff", "--", path],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        sys.exit(result.stderr.strip() or f"git diff failed for {path}")
    return result.stdout


def split_hunks(diff: str) -> tuple[list[str], list[list[str]]]:
    header: list[str] = []
    hunks: list[list[str]] = []
    for line in diff.splitlines(keepends=True):
        if line.startswith("@@"):
            hunks.append([line])
        elif hunks:
            hunks[-1].append(line)
        else:
            header.append(line)
    return header, hunks


def parse_indices(spec: str, total: int) -> list[int]:
    picked: set[int] = set()
    for part in spec.split(","):
        part = part.strip()
        if not part:
            continue
        if "-" in part:
            start, end = (int(x) for x in part.split("-", 1))
            picked.update(range(start, end + 1))
        else:
            picked.add(int(part))
    bad = [i for i in picked if i < 0 or i >= total]
    if bad:
        sys.exit(f"hunk index out of range: {bad} (file has {total} hunks)")
    return sorted(picked)


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("command", choices=["list", "stage"])
    parser.add_argument("path")
    parser.add_argument("hunks", nargs="?")
    parser.add_argument("--context", type=int, default=3)
    parser.add_argument("--brief", action="store_true", help="list: print only hunk headers")
    args = parser.parse_args()

    header, hunks = split_hunks(file_diff(args.path, args.context))
    if not hunks:
        sys.exit(f"no unstaged hunks in {args.path} (binary, untracked without `git add -N`, or already staged)")

    if args.command == "list":
        for index, hunk in enumerate(hunks):
            print(f"=== hunk {index} ===")
            sys.stdout.write(hunk[0] if args.brief else "".join(hunk))
        return

    if args.hunks is None:
        sys.exit("stage needs a hunk list, e.g. 0,2 or 1-3")

    selected = parse_indices(args.hunks, len(hunks))
    patch = "".join(header) + "".join("".join(hunks[i]) for i in selected)
    command = ["git", "apply", "--cached", "--recount", "-"]
    if args.context == 0:
        command.insert(3, "--unidiff-zero")
    result = subprocess.run(command, input=patch, capture_output=True, text=True)
    if result.returncode != 0:
        sys.exit(f"git apply failed:\n{result.stderr.strip()}")
    print(f"staged hunks {selected} of {args.path}")


if __name__ == "__main__":
    main()
