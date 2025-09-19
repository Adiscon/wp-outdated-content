### Title
chore(ci): WP.org Deploy-Workflows, readme.txt, uninstall.php, Compliance-Dokumente

### Summary (DE)
- Fuegt GitHub Actions fuer Deploy zu WordPress.org und fuer Asset-Updates hinzu.
- Liefert eine WP.org-konforme `readme.txt`.
- Fuegt `uninstall.php` im Plugin-Root hinzu.
- Fuegt `.distignore`, `.wordpress-org/` Asset-Ordner und Compliance-Checkliste hinzu.

### Changes
- `.github/workflows/deploy.yml`: Deploy zu WordPress.org bei Tags wie `1.0.1` (10up Action, `SLUG: wp-outdated-content`, `ASSETS_DIR: .wordpress-org`).
- `.github/workflows/assets.yml`: Manuell startbarer Workflow zum Aktualisieren der WordPress.org-Assets.
- `.distignore`: Schliesst Dev-Dateien aus dem Release aus.
- `.wordpress-org/`: Asset-Ordner (mit `.gitkeep`) fuer Banner/Icons/Screenshots.
- `readme.txt`: WP.org-Readme nach Spec (Short Description, Description, Installation, FAQ, Screenshots, Changelog, Upgrade Notice, Meta).
- `uninstall.php`: Loescht Optionen und Post-Metadaten der Per-Post-Overrides.
- `WPORG-COMPLIANCE-CHECKLIST.md`: Checkliste fuer Header, i18n, Security, Privacy, Uninstall, Performance, Kompatibilitaet, JSON-LD, Readme/Assets.
- `assets-manifest.md`: Spezifikation fuer Banner, Icons und Screenshots.
- `plugin-header.txt`: Header-Block-Vorlage fuer Hauptdatei.

### Requirements
- GitHub Secrets: `SVN_USERNAME`, `SVN_PASSWORD`.
- Assets in `.wordpress-org/` ablegen.
- Version konsistent: `1.0.1` in Plugin-Header und `readme.txt` (`Stable tag`).

### Usage
- Deploy: Tag pushen, z.B. `git tag 1.0.1 && git push origin 1.0.1`.
- Nur Assets: `assets.yml` via Actions UI manuell starten.

### Tests
- `readme.txt` mit Readme-Validator pruefen.
- Optional Dry-Run mit Test-Repo/Fork.

### BC/Security/Privacy
- Keine Runtime-Aenderungen. Keine Telemetrie. `uninstall.php` bereinigt Optionen/Meta.

