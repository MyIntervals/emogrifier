# Steps to release a new version

1. In the [composer.json](../composer.json), update the `branch-alias` entry to
   point to the release _after_ the upcoming release.
1. In the [CHANGELOG.md](../CHANGELOG.md), create a new section with subheadings
   for changes _after_ the upcoming release, set the version number for the
   upcoming release, and remove any empty sections.
1. Update the target milestone in the Dependabot configuration.
1. Create a pull request "Prepare release of version x.y.z" with those changes.
1. Have the pull request reviewed and merged.
1. In the [Releases tab](https://github.com/MyIntervals/emogrifier/releases),
   click 'Draft a new release'.
1. Select to create a new tag, e.g. `v8.2.0`, and the branch to use (or `main`).
1. Give the release a title, e.g. "V8.2.0: New features and bug fixes".
1. Copy the change log entries to the 'Release notes' box.
1. Check or uncheck 'Set as the latest release' accordingly.
1. Double-check everything is correct before clicking 'Publish release'.
1. Post about the new release on social media.
