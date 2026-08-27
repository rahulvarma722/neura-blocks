# Releasing

Two destinations, and they are not interchangeable.

| | GitHub Release | WordPress.org |
|---|---|---|
| Who it serves | anyone wanting a direct download | every plugin-directory user |
| What it is | a ZIP attached to a git tag | the official distribution point |
| Updates users | **no** | yes, via the dashboard |

WordPress.org Guideline 3: a stable version *must* be available from the
directory page, and distributing elsewhere while letting the directory copy go
stale can get a plugin removed. GitHub releases are an addition, never a
replacement.

Guideline 8: publishing a downloadable ZIP is fine. A plugin that **updates
itself** from anywhere other than WordPress.org is not. Do not add a
GitHub-based update checker.

## Cutting a release

```bash
# 1. Bump the version in ALL THREE places — they must agree or the build refuses.
#      blockkit.php   Version: header
#      blockkit.php   BLOCKKIT_VERSION
#      readme.txt     Stable tag
#
# 2. Add a changelog entry to readme.txt.
#
# 3. Verify locally. This runs every gate the CI will.
./bin/build-zip.sh

# 4. Commit, tag, push. The tag is what triggers the release.
git commit -am "release: 0.0.2"
git tag v0.0.2
git push origin main --tags
```

`.github/workflows/release.yml` then rebuilds from a clean checkout, refuses if
the tag disagrees with the `Version:` header, and attaches two assets:

- `blockkit-0.0.2.zip` — the archive for that version
- `blockkit.zip` — a stable name, so this link always resolves to the newest:
  `https://github.com/rahulvarma722/blockkit/releases/latest/download/blockkit.zip`

The release runs `bin/build-zip.sh`, so PHPCS, ESLint, Stylelint, the readme
checks and the staged-tree assertions all have to pass. A lint failure fails the
release rather than publishing an unvetted ZIP.

### Without the workflow

```bash
./bin/build-zip.sh
gh release create v0.0.2 dist/blockkit-0.0.2.zip --generate-notes
```

### Re-running a botched release

The workflow has a `workflow_dispatch` trigger taking a tag, so a failed asset
upload can be retried without moving the tag.

## Why the ZIP is not committed

A ZIP in the repo is a binary git can never forget: every version adds its full
size to the history permanently, and the diff says nothing. Release assets live
outside the object store.

It also keeps `dist/` gitignored, which matters beyond tidiness — Plugin Check
reports a compressed file inside a plugin directory as an **error**, and the
Developer FAQ states plainly that ZIPs may not be included in a plugin.

## Then WordPress.org

```bash
svn co https://plugins.svn.wordpress.org/blockkit
# copy the ZIP's CONTENTS into trunk/ — not the ZIP
svn cp trunk tags/0.0.2
svn ci -m "Release 0.0.2"
```

Set `Stable tag` in `trunk/readme.txt` to the tagged version. Icons, banners and
screenshots go in SVN `assets/` at the repository root — never inside the
plugin ZIP.
