=== SessionTags ===
Contributors: example-author
Tags: session, url, parameter, shortcode, elementor
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SessionTags speichert URL-Parameter in der PHP-Session und stellt Shortcodes für deren Ausgabe bereit, mit Unterstützung für URL-Kürzung und -Verschleierung.

== Beschreibung ==

SessionTags ist ein WordPress-Plugin, das URL-Parameter erkennt, deren Werte für die Dauer der aktuellen Benutzersitzung in der PHP-Session speichert und verschiedene Möglichkeiten zur Ausgabe dieser gespeicherten Werte anbietet.

==== SessionTags ===
Contributors: example-author
Tags: session, url, parameter, shortcode
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SessionTags speichert vordefinierte URL-Parameter in der PHP-Session und stellt einen Shortcode für deren Ausgabe bereit.

== Beschreibung ==

SessionTags ist ein WordPress-Plugin, das URL-Parameter erkennt, deren Werte für die Dauer der aktuellen Benutzersitzung in der PHP-Session speichert und einen Shortcode bereitstellt, um diese gespeicherten Werte auf der Website auszugeben.

= Hauptfunktionen =

* Erfassung bestimmter URL-Parameter beim Seitenaufruf
* Speicherung der Parameter in der PHP-Session für die Dauer der Browsersitzung
* Ausgabe der gespeicherten Parameter über einen einfachen Shortcode

= Anwendungsbeispiele =

* Tracking von Herkunftskampagnen
* Personalisierung von Inhalten basierend auf URL-Parametern
* Speicherung von Benutzereinstellungen über mehrere Seiten hinweg

== Installation ==

1. Laden Sie die Plugin-Dateien hoch und entpacken Sie sie in das Verzeichnis `/wp-content/plugins/sessiontags/`.
2. Aktivieren Sie das Plugin über das Menü 'Plugins' in WordPress.
3. Konfigurieren Sie die zu verfolgenden URL-Parameter im Hauptcode des Plugins (standardmässig: 'quelle', 'kampagne', 'id').

== Verwendung ==

= Konfiguration der zu verfolgenden Parameter =

Standardmässig werden die Parameter 'quelle', 'kampagne' und 'id' verfolgt. Um diese anzupassen, bearbeiten Sie die Hauptdatei des Plugins (`sessiontags.php`) und ändern Sie das Array `$tracked_params`:

```php
private $tracked_params = ['quelle', 'kampagne', 'id'];
```

= Verwendung des Shortcodes =

Es stehen zwei Shortcode-Varianten zur Verfügung:

**Kurzer Shortcode (empfohlen):**
```
[st k="parameter_name" d="standardwert"]
```

**Vollständiger Shortcode:**
```
[show_session_param key="parameter_name" default="standardwert"]
```

Beide Varianten sind funktional identisch, der kurze Shortcode ist jedoch einfacher einzugeben. Parameter:

* `k` oder `key`: Der Name des URL-Parameters (erforderlich)
* `d` oder `default`: Ein optionaler Standardwert, der angezeigt wird, wenn der Parameter nicht in der Session gespeichert ist

Beispiele:

```
[st k="quelle"]
[st k="kampagne" d="standard-kampagne"]
```

Dies würde folgendes ausgeben:

```
newsletter
standard-kampagne (falls "kampagne" nicht in der Session vorhanden ist)
```

== Häufig gestellte Fragen ==

= Wie lange bleiben die Parameter gespeichert? =

Die Parameter bleiben für die Dauer der PHP-Session gespeichert. Das bedeutet typischerweise, bis der Benutzer den Browser schliesst oder eine bestimmte Zeit der Inaktivität erreicht ist (abhängig von der PHP-Konfiguration des Servers).

= Kann ich die Parameter auch in einer Datenbank speichern? =

Dies ist in der aktuellen Version nicht vorgesehen, da das Plugin absichtlich leichtgewichtig gehalten wurde. Für eine dauerhafte Speicherung empfehlen wir die Verwendung von Cookies oder eine Erweiterung des Plugins.

= Ist das Plugin DSGVO-konform? =

Das Plugin selbst speichert Daten nur in der PHP-Session des Benutzers und nicht dauerhaft. Dennoch sollten Sie Ihre Datenschutzerklärung entsprechend anpassen, wenn Sie personenbezogene Daten in URL-Parametern übertragen und speichern.

== Changelog ==

= 1.0.0 =
* Erstveröffentlichung
