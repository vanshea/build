# Build website

This folder is the separate `vanshea/build` GitHub repository. Publish its public
files at the document root of https://build.vanshea.com, without a `/build/` URL
prefix. Canonical and social URLs use the build domain; HTML pages carry noindex
so this review copy does not compete with the production site.

From `/Library/WebServer/Documents`:

- Check both static sites: `npm run static:check`.
- Create the Build upload package: `npm run build:bundle`.
- Find it in `docs/releases/build-static-upload/ftp-upload/`.
- See `docs/STATIC-DOMAIN-DEPLOYMENT.md` for the full deployment and upload lists.

Local preview from this folder: `python3 -m http.server 8766 --bind 127.0.0.1`.
Open http://127.0.0.1:8766/. This previews the same domain-root path structure.

The existing Deploy Build workflow runs when main is pushed or when manually
started from GitHub Actions. It validates local links before uploading to the
`Siteground Build` environment. Its `DEPLOY_PATH` must be the Build host's
document root as seen by that FTP account, with a trailing slash. The workflow
uses the existing FTP_SERVER, FTP_USERNAME, and FTP_PASSWORD secrets.

After reviewing the file changes, stage only the public release and helpers:
`git add --pathspec-from-file=.github/scripts/GITHUB-FILES.txt` from this folder.
Inspect the staged changes before committing and pushing. Pushing main triggers
the existing FTP deployment to the Build document root. `GITHUB-FILES.txt` also includes new assets that
were present locally but had not yet been tracked in Git.

The static upload excludes WordPress, nested preview copies, Git internals,
source templates, editor metadata, and local logs. Blog links intentionally
open https://www.vanshea.com/blog/.

`npm run variants:sync` in the parent project regenerates both folders from
older root sources. Do not use it during this release; it can replace direct
edits and copy preview prefixes back into these files.
