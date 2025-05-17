# SessionTags 🏷️

## WordPress URL Parameter Tracking Plugin

SessionTags captures URL parameters and stores them in PHP sessions for personalized website experiences. Create dynamic content based on referral sources, campaigns, and user preferences.

🔗 **Download the plugin: [samuelbaer.ch/sessiontags](https://samuelbaer.ch/sessiontags)**

## Features ✨

- 🔌 **Easy Integration**: Use shortcodes, Elementor dynamic tags, or Avada Fusion Builder elements
- 🔐 **URL Encoding**: Optional parameter encryption for improved security and reduced readability
- 📝 **Form Integration**: Pre-fill Google & Microsoft Forms with your session parameters
- 🔄 **URL Generator**: Create links that automatically pass your parameters to other pages
- 📱 **Short Parameters**: Configure abbreviated parameter names for cleaner URLs
- ↪️ **Redirection**: Set up automatic redirects based on specific parameters

## Installation 🚀

1. Download the plugin from [samuelbaer.ch/sessiontags](https://samuelbaer.ch/sessiontags)
2. Upload to your WordPress site through the admin panel (Plugins > Add New > Upload Plugin)
3. Activate the plugin
4. Configure your parameters under Settings > SessionTags

## Usage 💡

### Basic Parameter Display

```
[st k="source" d="direct"]
```

Or use the longer version:

```
[show_session_param key="source" default="direct"]
```

### Link Generation with Parameters

```
[st_url url="https://example.com/landing/" params="source=[st k=source],campaign=[st k=campaign]"]Click here[/st_url]
```

### Elementor Integration

1. Edit any Elementor text widget
2. Click the dynamic content icon
3. Select "SessionTags" from the dropdown
4. Choose your parameter and optional fallback value

### Form Integration

```
[st_form type="google" url="https://docs.google.com/forms/d/e/YOUR_FORM_ID/viewform" params="name,email" form_params="entry.1234567890,entry.2345678901"]
```

## Documentation 📖

Full documentation is available in the WordPress admin under Settings > SessionTags > Documentation.

## Compatibility 🔄

- WordPress 5.0+
- PHP 7.2+
- Elementor (optional)
- Avada Fusion Builder (optional)

## License 📜

GPLv2 or later

---

# SessionTags 🏷️

## WordPress URL-Parameter-Tracking Plugin

SessionTags erfasst URL-Parameter und speichert sie in PHP-Sessions für personalisierte Website-Erlebnisse. Erstelle dynamische Inhalte basierend auf Verweisquellen, Kampagnen und Benutzereinstellungen.

🔗 **Plugin herunterladen: [samuelbaer.ch/sessiontags](https://samuelbaer.ch/sessiontags)**

## Funktionen ✨

- 🔌 **Einfache Integration**: Verwende Shortcodes, Elementor Dynamic Tags oder Avada Fusion Builder Elemente
- 🔐 **URL-Verschleierung**: Optionale Parameter-Verschlüsselung für verbesserte Sicherheit und reduzierte Lesbarkeit
- 📝 **Formular-Integration**: Fülle Google & Microsoft Forms automatisch mit deinen Session-Parametern aus
- 🔄 **URL-Generator**: Erstelle Links, die deine Parameter automatisch an andere Seiten weitergeben
- 📱 **Kurze Parameter**: Konfiguriere abgekürzte Parameter-Namen für übersichtlichere URLs
- ↪️ **Weiterleitung**: Richte automatische Weiterleitungen basierend auf bestimmten Parametern ein

## Installation 🚀

1. Lade das Plugin von [samuelbaer.ch/sessiontags](https://samuelbaer.ch/sessiontags) herunter
2. Lade es über das Admin-Panel auf deine WordPress-Seite hoch (Plugins > Neu hinzufügen > Plugin hochladen)
3. Aktiviere das Plugin
4. Konfiguriere deine Parameter unter Einstellungen > SessionTags

## Verwendung 💡

### Einfache Parameter-Anzeige

```
[st k="quelle" d="direkt"]
```

Oder verwende die längere Version:

```
[show_session_param key="quelle" default="direkt"]
```

### Link-Generierung mit Parametern

```
[st_url url="https://beispiel.de/landingpage/" params="quelle=[st k=quelle],kampagne=[st k=kampagne]"]Hier klicken[/st_url]
```

### Elementor-Integration

1. Bearbeite ein beliebiges Elementor-Textwidget
2. Klicke auf das Dynamic-Content-Symbol
3. Wähle "SessionTags" aus dem Dropdown-Menü
4. Wähle deinen Parameter und einen optionalen Fallback-Wert

### Formular-Integration

```
[st_form type="google" url="https://docs.google.com/forms/d/e/DEINE_FORMULAR_ID/viewform" params="name,email" form_params="entry.1234567890,entry.2345678901"]
```

## Dokumentation 📖

Die vollständige Dokumentation ist im WordPress-Admin unter Einstellungen > SessionTags > Dokumentation verfügbar.

## Kompatibilität 🔄

- WordPress 5.0+
- PHP 7.2+
- Elementor (optional)
- Avada Fusion Builder (optional)