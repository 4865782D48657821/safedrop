# Produktkonzept: Gaming Discovery & Creator Platform

**Dokumentstatus:** Konzept v0.4  
**Schwerpunkt:** Minecraft- und Roblox-Projekte  
**Ziel:** Konsolidierte Grundlage für MVP-PRD, Geschäftsmodell und technische Architektur

## 1. Executive Summary

Die Plattform ist eine sichere, personalisierte Discovery- und Publishing-Plattform für junge Gaming-Communities. Creator können Minecraft- und Roblox-Projekte veröffentlichen, verwalten und über externe Ziele zugänglich machen. Nutzer entdecken relevante Inhalte über einen personalisierten Feed, folgen Creatorn und werden über neue Projekte, Releases sowie externe Livestreams informiert.

Im MVP hostet die Plattform keine Dateien. Sie verwaltet strukturierte Projektinformationen und leitet Nutzer nach einer Sicherheitsprüfung auf beliebige externe Domains weiter. Eigenes Datei-Hosting ist als spätere Ausbaustufe vorgesehen.

Junior Creator dürfen selbst veröffentlichen, aber weder monetarisieren noch Werbung auf ihren Projektseiten anzeigen lassen. Monetarisierung steht ausschließlich volljährigen und verifizierten Creatorn zur Verfügung. Werbekampagnen werden zunächst manuell durch den Plattformbetreiber betreut.

Das Produkt verbindet vier Funktionen:

1. sicheres Veröffentlichen und Verwalten von Gaming-Projekten,
2. personalisierte Content Discovery,
3. leichtgewichtige soziale Funktionen,
4. kontrollierte Monetarisierung für volljährige Creator.

## 2. Produktvision

> Die vertrauenswürdige Anlaufstelle für junge Gamer, um neue Projekte zu entdecken, verantwortungsvollen Creatorn zu folgen und sicher zu deren Inhalten zu gelangen.

Die Plattform wird nicht primär als „Link-Locker“ positioniert. Der kontrollierte Redirect ist ein technischer Bestandteil, nicht das zentrale Markenversprechen. Im Vordergrund stehen Discovery, Sicherheit, Creator-Beziehungen und transparente Monetarisierung.

## 3. Zielgruppen

### 3.1 Besucher und Mitglieder

- 12 bis 21 Jahre
- überwiegend männlich
- Gaming-affin
- Mobile und Desktop
- perspektivisch Südostasien und Südamerika
- besonderes Interesse an Minecraft und Roblox

### 3.2 Creator

- Junior Creator unter 18 Jahren
- volljährige Hobby- und Professional Creator
- Streamer mit YouTube- oder Twitch-Kanal
- Mod-, Plugin-, Asset- und Experience-Entwickler

### 3.3 Werbetreibende

- Anbieter altersgerechter Gaming-Produkte
- Hardware- und Zubehörmarken
- Hosting- und Serveranbieter
- Entwicklerwerkzeuge und Creator-Produkte
- ausgewählte Spiele- und Entertainment-Anbieter

### 3.4 Plattformbetreiber

- verantwortet Produkt, Moderation, Sicherheit und Werbequalität
- erhält einen Anteil an zulässigen Werbeerlösen
- betreut Werbekampagnen im MVP manuell

## 4. Strategische Positionierung

### Für Nutzer

> Entdecke relevante Minecraft- und Roblox-Projekte und gelange transparent und sicher zum jeweiligen Inhalt.

### Für Creator

> Veröffentliche und verwalte deine Projekte, baue eine Community auf und informiere Follower über Releases und Livestreams.

### Für volljährige Creator

> Monetarisiere qualifizierte Reichweite in einem kontrollierten und markensicheren Umfeld.

### Für Werbetreibende

> Erreiche Gaming-Zielgruppen über manuell betreute und klar gekennzeichnete Kampagnen.

## 5. Festgelegte Produktentscheidungen

| Bereich | Entscheidung |
|---|---|
| Markenarchitektur | Eine gemeinsame Plattform für Minecraft und Roblox |
| Primäre Inhalte | Minecraft- und Roblox-Projekte |
| Übergeordnetes Inhaltsobjekt | Projekt |
| Junior Creator | Dürfen selbst veröffentlichen |
| Junior-Monetarisierung | Nicht erlaubt |
| Junior-Projektseiten | Werbefrei |
| Datei-Hosting im MVP | Nein |
| Späteres Datei-Hosting | Vorgesehen |
| Externe Ziele | Beliebige Domains nach Prüfung |
| Discovery | Personalisierter Feed |
| Nutzerbewertungen | Vorgesehen |
| Gastzugriff | Öffentliches Browsing ohne Konto |
| Konto erforderlich | Personalisierung, Interaktion und Veröffentlichung |
| Livestreams | Extern auf YouTube oder Twitch |
| Live-Funktion | Status, Benachrichtigung und Weiterleitung |
| Werbemodell im MVP | Manuell betreute Kampagnen |
| Veröffentlichungsprüfung | TBD |
| Creator-Verifizierung | TBD |
| Pilotmarkt | TBD |

## 6. Produktprinzipien

1. **Safety before Growth:** Unsichere Inhalte werden vor dem Ranking ausgeschlossen und nicht nur schlechter gerankt.
2. **Transparenz:** Werbung, externe Ziele und Downloadplattformen werden eindeutig gekennzeichnet.
3. **Keine Dark Patterns:** Keine irreführenden Buttons, künstlichen Countdowns oder erzwungenen Werbeklicks.
4. **Jugendschutz by Design:** Junior-Content bleibt werbefrei; sensible Funktionen sind alters- und rollenabhängig.
5. **Creator Ownership:** Creator verwalten ihre Projektinformationen, Releases und externe Ziele.
6. **Kontrollierte Personalisierung:** Relevanz wird nicht ausschließlich über Klickrate und Verweildauer optimiert.
7. **Offenes Ökosystem:** Beliebige externe Domains sind möglich, sofern sie die Sicherheitsprüfung bestehen.

## 7. Inhaltsmodell

Das gemeinsame Kernobjekt heißt **Projekt**. Dadurch können Minecraft- und Roblox-Inhalte in einem gemeinsamen System verwaltet werden, ohne Roblox-Inhalte künstlich als Mods zu behandeln.

### 7.1 Minecraft-Projekttypen

- Mod
- Modpack
- Resource Pack
- Shader
- Map
- Plugin
- Datapack
- Server
- Tool

### 7.2 Roblox-Projekttypen

- Experience
- Asset
- Model
- Plugin
- Development Tool
- Resource
- Tutorial
- Community-Projekt

### 7.3 Gemeinsame Projektfelder

- Titel und Kurzbeschreibung
- vollständige Beschreibung
- Spiel und Projekttyp
- Kategorien und Tags
- Bilder und optionale Videos
- Creator und Mitwirkende
- Sprache
- Lizenz- beziehungsweise Rechteangabe
- externe Projektseite
- Veröffentlichungsstatus
- Moderationsstatus
- Inhalts- und Alterseinstufung
- Erstellungs- und Aktualisierungszeitpunkt

### 7.4 Minecraft-spezifische Felder

- Java oder Bedrock
- Minecraft-Version
- Mod-Loader
- Client, Server oder beides
- Abhängigkeiten
- erforderliche Mods oder Komponenten

### 7.5 Roblox-spezifische Felder

- Experience- oder Ressourcentyp
- Genre und Zielgruppe
- unterstützte Geräte
- öffentliche Experience- oder Asset-ID
- Zugangsmodell
- Installations- oder Verwendungshinweise
- offizielle Roblox-Zuordnung

### 7.6 Release

Ein Projekt kann mehrere Releases besitzen:

- Versionsnummer
- Changelog
- Kompatibilitätsinformationen
- Veröffentlichungsdatum
- externe Ziele
- Status und Moderationsergebnis

### 7.7 Externes Ziel

- ursprüngliche URL
- normalisierte URL
- aufgelöste Redirect-Kette
- Ziel-Domain
- Zieltyp, zum Beispiel Projektseite oder Download
- Zeitpunkt der letzten Prüfung
- Erreichbarkeit
- Sicherheits- und Vertrauensstatus

## 8. Rollen- und Altersmodell

| Rolle | Zentrale Rechte |
|---|---|
| Gast | Browsen, suchen, Projekt- und Creator-Seiten ansehen, externe Ziele öffnen, Inhalte melden |
| Mitglied | Personalisierung, folgen, speichern, bewerten, Benachrichtigungen verwalten |
| Junior Creator | Projekte und Releases veröffentlichen, Creator-Profil verwalten, Basisstatistiken einsehen |
| Adult Creator, nicht verifiziert | Creator-Funktionen ohne Monetarisierung |
| Adult Creator, verifiziert | Monetarisierung, Einnahmen, Auszahlungen und erweiterte Statistiken |
| Werbekunde | Kampagnenbriefing und Reporting; Kampagnen werden manuell betreut |
| Moderator | Inhalte, Links, Meldungen und Rechtefälle prüfen |
| Administrator | Rollen, Richtlinien, Werbung, Finanzen und Plattformbetrieb verwalten |

### 8.1 Interne Altersgruppen

- `JUNIOR`
- `ADULT_UNVERIFIED`
- `ADULT_VERIFIED`

Der konkrete Minderjährigenstatus wird nicht öffentlich angezeigt. Werbe- und Monetarisierungsentscheidungen werden serverseitig aus Rolle, Verifizierungsstatus und Content-Eignung abgeleitet.

## 9. Junior Creator

Junior Creator dürfen:

- ein Creator-Profil betreiben,
- Projekte und Releases selbst veröffentlichen,
- externe Links hinterlegen,
- Collections erstellen,
- YouTube- und Twitch-Kanäle verbinden,
- Basisstatistiken einsehen.

Junior Creator dürfen nicht:

- Werbeerlöse erhalten,
- Auszahlungsdaten hinterlegen,
- bezahlte Kampagnen über die Plattform abschließen,
- Affiliate-Monetarisierung über Plattformfunktionen aktivieren.

Projektseiten von Junior Creatorn enthalten:

- keine Displaywerbung,
- keine Interstitials,
- keine gesponserten Empfehlungen,
- keine Revenue-Share-Werbung.

Die spätere Freischaltung der Monetarisierung erfordert Volljährigkeit sowie eine gesonderte Identitäts- und Auszahlungsverifizierung. Sie erfolgt nicht allein aufgrund eines im Profil hinterlegten Geburtstags.

## 10. Gastzugriff und Konten

### 10.1 Ohne Konto

Gäste können:

- öffentliche Bereiche durchsuchen,
- einen allgemeinen Discovery-Feed nutzen,
- Projekt- und Creator-Seiten öffnen,
- externe Ziele aufrufen,
- Live-Status sehen,
- problematische Inhalte melden.

Der allgemeine Feed kann Sprache, Region, gewähltes Spiel, Trends, redaktionelle Empfehlungen und kurzfristige Session-Signale berücksichtigen. Eine dauerhafte persönliche Profilbildung erfolgt erst mit Konto und entsprechender Transparenz.

### 10.2 Mit Konto

Mitglieder können zusätzlich:

- Interessen und Spiele festlegen,
- einen personalisierten Feed erhalten,
- Creatorn und Projekten folgen,
- Projekte speichern und bewerten,
- „Nicht interessiert“ angeben,
- Benachrichtigungen konfigurieren,
- eigene Projekte veröffentlichen.

## 11. Personalisierter Feed

### 11.1 Kandidatenquellen

- Projekte gefolgter Creator
- neue Releases abonnierter Projekte
- bevorzugte Spiele, Kategorien und Projekttypen
- ähnliche Projekte
- regionale und sprachliche Trends
- neue geprüfte Creator
- redaktionell kuratierte Inhalte
- aktive Livestreams gefolgter Creator

### 11.2 Positive Ranking-Signale

- Follow-Beziehungen
- gespeicherte Projekte
- positive Bewertungen
- bevorzugte Spiele und Kategorien
- Versions- und Plattformkompatibilität
- erfolgreiche externe Weiterleitungen
- Aktualität
- Creator- und Domain-Vertrauen
- organisches Engagement

### 11.3 Negative Signale

- „Nicht interessiert“
- negative Bewertungen mit plausibler Nutzungshistorie
- wiederholte Meldungen
- defekte oder unsichere Ziele
- Clickbait oder irreführende Metadaten
- verdächtiges Engagement
- inhaltliche Wiederholungen

### 11.4 Harte Filter vor dem Ranking

Ein Projekt wird nur berücksichtigt, wenn:

- es veröffentlicht ist,
- es den erforderlichen Prüfstatus besitzt,
- mindestens ein erreichbares Ziel vorhanden ist,
- keine aktive Sicherheits- oder Moderationssperre besteht,
- Nutzerpräferenzen oder Altersregeln es nicht ausschließen.

### 11.5 Cold Start

Neue Mitglieder wählen beim Onboarding:

- Minecraft, Roblox oder beides,
- bevorzugte Kategorien und Projekttypen,
- relevante Spielversionen und Plattformen,
- bekannte Creator.

Der erste Feed kombiniert diese Angaben mit redaktionell geprüften, aktuellen und populären Projekten.

## 12. Bewertungen

Mitglieder dürfen Projekte bewerten. Für den MVP wird eine einfache Bewertung ohne öffentliche Freitextrezension empfohlen, um Moderations-, Beleidigungs- und Manipulationsrisiken zu begrenzen.

### Empfohlenes MVP-Modell

- „Hilfreich“ beziehungsweise „Gefällt mir“
- optionales negatives Qualitätssignal: „Nicht hilfreich“
- „Nicht interessiert“ bleibt privat und beeinflusst nur den persönlichen Feed
- technische oder sicherheitsbezogene Probleme werden über das Meldesystem erfasst
- keine anonyme Bewertung ohne Konto

### Schutz vor Manipulation

- Rate Limits
- Mindestalter des Kontos oder Mindestaktivität für gewichtete Bewertungen
- Erkennung verknüpfter Konten und ungewöhnlicher Bewertungsmuster
- geringere Gewichtung neuer oder auffälliger Konten
- keine direkte Umsatzberechnung allein anhand von Bewertungen
- Moderationsmöglichkeit bei Brigading

Ein öffentliches Fünf-Sterne-System wird für den MVP nicht empfohlen, weil es Scheingenauigkeit erzeugt und leichter gezielt manipuliert werden kann.

## 13. Externe Domains und Weiterleitungen

Beliebige Domains sind grundsätzlich erlaubt. Jede URL wird als potenziell veränderliches Ziel behandelt.

### 13.1 Prüfprozess

1. URL normalisieren
2. Domain und DNS prüfen
3. Redirect-Kette auflösen
4. bekannte Phishing-, Malware- und Abuse-Signale prüfen
5. Dateityp und Zielart bewerten
6. URL-Shortener und verschleierte Ziele erkennen
7. Ergebnis und Prüfzeitpunkt speichern
8. Ziel regelmäßig erneut prüfen
9. bei URL- oder Redirect-Änderungen erneut bewerten
10. riskante Weiterleitungen blockieren

### 13.2 Domain-Status

| Status | Verhalten |
|---|---|
| Trusted | Normale Weiterleitung |
| Known | Normale Weiterleitung mit transparenter Zielanzeige |
| New | Verstärkte Prüfung, gegebenenfalls Nutzerhinweis |
| Suspicious | Warnung oder Blockierung |
| Blocked | Veröffentlichung und Zugriff gesperrt |
| Unreachable | Projekt vorübergehend als nicht verfügbar markieren |

### 13.3 Schutz vor nachträglichen Änderungen

- wiederkehrende Scans
- Neuprüfung bei veränderter Redirect-Kette
- automatische Sperre bei kritischem Zielwechsel
- Kill Switch für einzelne URLs und vollständige Domains
- Meldemöglichkeit auf jeder Projektseite
- protokollierte Moderationsentscheidungen

## 14. Livestream-Integration

Die Plattform hostet keine Streams. Creator verbinden einen verifizierten YouTube- oder Twitch-Kanal. Die Plattform erkennt Live-Ereignisse, zeigt den Status und leitet zum Streamingdienst weiter.

### MVP-Funktionen

- YouTube- oder Twitch-Kanal verbinden und verifizieren
- Live-Status erkennen
- Live-Badge auf dem Creator-Profil
- Live-Karte im Following-Feed
- In-App- und Web-Push-Benachrichtigung
- direkte Weiterleitung zum externen Stream
- doppelte Benachrichtigungen vermeiden
- Stream-Ende erkennen

### Nutzersteuerung

Benachrichtigungen können pro Creator getrennt aktiviert werden für:

- neue Projekte,
- neue Releases,
- Livestreams.

Pro Nutzer und Stream-Session wird höchstens eine Live-Benachrichtigung gesendet.

## 15. Werbe- und Monetarisierungsmodell

### 15.1 MVP-Modell

Werbekampagnen werden manuell durch den Plattformbetreiber betreut. Es gibt zunächst keinen offenen Self-Service-Marktplatz und kein automatisches Real-Time Bidding.

Der Ablauf:

1. Werbekunde reicht Kampagnenbriefing und Werbemittel ein.
2. Plattform prüft Produkt, Zielseite, Zielgruppe und Werbemittel.
3. Plattform legt zulässige Spiele, Kategorien, Regionen und Frequenzen fest.
4. Kampagne wird manuell aktiviert und überwacht.
5. Werbekunde erhält ein kontrolliertes Reporting.

### 15.2 Werbeprinzipien

- Werbung wird eindeutig gekennzeichnet.
- Keine irreführenden Downloadbuttons oder Systemwarnungen.
- Keine Werbung auf Junior-Projektseiten.
- Keine Glücksspiel-, Dating-, Alkohol-, Tabak- oder Erwachsenenangebote.
- Keine Angebote für Accountdiebstahl, Cheats, Scams oder fragwürdige In-Game-Währungen.
- Zielseiten werden vor und während der Kampagne geprüft.
- Kontextuelle Ausspielung wird gegenüber sensibler Verhaltensprofilierung bevorzugt.
- Alters-, Regional- und Frequenzregeln werden technisch erzwungen.

### 15.3 Creator Revenue Share

- ausschließlich für volljährige und verifizierte Creator
- Einnahmen zunächst als ausstehend markieren
- Fraud-Sperrfrist vor Auszahlung
- Mindestbetrag für Auszahlungen
- Rückbehalt bei auffälligem Traffic
- keine Bezahlung für erzwungene oder eigene Werbeklicks

## 16. Trust & Safety

### 16.1 Verbotene Inhalte

- Malware, Phishing und Credential-Diebstahl
- Scams und gefälschte Giveaways
- sexuelle oder ausbeuterische Inhalte
- Hass, Gewaltverherrlichung und gezielte Belästigung
- jugendgefährdende oder illegale Inhalte
- urheberrechtsverletzende Kopien
- irreführende Downloads
- Inhalte oder Tools zur Umgehung von Schutzmaßnahmen, sofern unzulässig

### 16.2 Moderationsbausteine

- automatisierte Inhalts- und Linkprüfung
- Meldesystem für Nutzer
- Moderationswarteschlange
- Rechteinhaber- und Takedown-Prozess
- Wiederholungstäter-System
- Einspruchsverfahren
- Audit-Logs
- Domain- und Account-Sperren
- Eskalationsprozess für akute Risiken

### 16.3 Offene Entscheidung: Veröffentlichungsprüfung

Zu entscheiden ist zwischen:

- Vorabprüfung aller Projekte,
- Vorabprüfung nur neuer oder riskanter Creator,
- sofortiger Veröffentlichung mit nachgelagerter Prüfung.

Empfohlene Richtung: risikobasierte Vorabprüfung. Neue Creator, neue Domains und riskante Zieltypen werden vor Veröffentlichung geprüft; etablierte Creator können nach positiver Historie schneller veröffentlichen.

## 17. Bedrohungsmodell

### 17.1 Scraping und Bypasser

- kurzlebige signierte Weiterleitungen
- Rate Limits und Bot-Erkennung
- serverseitige Zielauflösung
- Scraper-Fingerprints und Anomalieerkennung
- Metadaten nur bedarfsgerecht ausliefern
- Produktwert nicht allein auf versteckten Ziel-URLs aufbauen

### 17.2 Urheberrechtsverletzungen

- Rechtebestätigung bei Veröffentlichung
- Notice-and-Takedown-Prozess
- Counter-Notice- beziehungsweise Einspruchsprozess
- Wiederholungstäter-Regeln
- nachvollziehbare Fallakten und Audit-Logs
- vorübergehendes Einfrieren betroffener Einnahmen

### 17.3 Datenleaks und API-Missbrauch

- minimale Datenerhebung
- objektbezogene Autorisierungsprüfung
- Trennung öffentlicher und interner APIs
- kurzlebige Sessions und Token-Rotation
- Verschlüsselung sensibler Daten
- minimale Rollenberechtigungen
- administrative Audit-Logs
- Incident-Response-Prozess

### 17.4 Fraud und Bot-Traffic

- Einnahmen nicht sofort gutschreiben
- Geräte-, Netzwerk- und Verhaltenssignale auswerten
- eigene Creator-Aktivität ausschließen
- Anomalien pro Creator, Kampagne und Ziel erkennen
- Auszahlungen risikobasiert prüfen
- verknüpfte Konten analysieren
- Rückforderung und Sperrung bei bestätigtem Missbrauch

### 17.5 Bewertungsmanipulation

- Rate Limits und Vertrauensgewichtung
- ungewöhnliche Bewertungscluster erkennen
- Brigading und koordinierte Angriffe moderieren
- Bewertungen nicht direkt in Auszahlungen übersetzen

## 18. Informationsarchitektur

### 18.1 Öffentliche Bereiche

- Startseite
- Discover
- Minecraft
- Roblox
- Suche und Filter
- Projektseite
- Creator-Profil
- Live-Übersicht
- Sicherheitsinformationen
- Meldeformular

### 18.2 Bereiche nach Anmeldung

- personalisierter Home-Feed
- Following
- gespeicherte Projekte
- Bewertungen und Aktivitäten
- Benachrichtigungen
- Kontoeinstellungen
- Creator Studio

### 18.3 Creator Studio

- Projekte
- Releases
- externe Ziele
- Veröffentlichungs- und Moderationsstatus
- Collections
- YouTube-/Twitch-Verbindungen
- Basisstatistiken
- Monetarisierung für verifizierte Erwachsene

### 18.4 Interne Bereiche

- Moderationswarteschlange
- URL- und Domainprüfung
- Meldungen und Nutzerfälle
- Rechteinhaber- und Takedown-Fälle
- Fraud-Übersicht
- Werbekampagnen und Werbemittelprüfung
- Audit-Logs

## 19. MVP-Umfang

### 19.1 Bestandteil des MVP

- Registrierung, Anmeldung und Altersgruppen
- öffentliche Gastnutzung
- Creator-Profile
- Minecraft- und Roblox-Projekte
- Releases und Changelogs
- beliebige externe URLs mit Sicherheitsprüfung
- Suche, Filter und Kategorien
- personalisierter Feed
- Folgen und Speichern
- einfache Projektbewertungen
- „Nicht interessiert“
- Benachrichtigungszentrum
- YouTube-/Twitch-Verknüpfung
- Live-Status und Live-Benachrichtigungen
- Meldesystem
- Moderationsoberfläche
- Creator-Basisstatistiken
- manuell betreute Werbekampagnen
- Monetarisierung für verifizierte Erwachsene
- grundlegende Fraud-Erkennung

### 19.2 Nicht Bestandteil des MVP

- eigenes Datei-Hosting
- Direktnachrichten
- offene Freitextkommentare oder Rezensionen
- Gruppen und Foren
- native Livestreaming-Infrastruktur
- offener Self-Service-Werbemarktplatz
- Real-Time Bidding
- Creator-Monetarisierung unter 18
- frei handelbare Punkte oder Kryptowährungen

## 20. Vorbereitung auf eigenes Datei-Hosting

Das Datenmodell soll bereits folgende spätere Funktionen berücksichtigen:

- Dateien pro Release
- mehrere Builds und Plattformvarianten
- Datei-Hashes und Signaturen
- Malware-Scans und Quarantäne
- Abhängigkeiten
- Downloadregionen und CDN
- Storage- und Bandbreitenlimits
- Entfernung einzelner Dateien
- Downloadstatistiken
- Spiegelserver

Mögliche Entwicklung:

1. externe Projektseiten,
2. externe Direktlinks,
3. optionales Spiegeln ausgewählter Dateien,
4. eigener Upload für verifizierte Creator,
5. vollständiges Hosting.

## 21. Erfolgsmessung

### North-Star-Metric

> Erfolgreiche, vertrauenswürdige Content-Weiterleitungen pro aktivem Nutzer.

### Ergänzende Kennzahlen

- wiederkehrende Besucher
- Aktivierungsrate nach Registrierung
- Follows und gespeicherte Projekte pro aktivem Nutzer
- Feed-Interaktionsrate
- erfolgreiche externe Weiterleitungen
- Creator-Retention
- neue und aktualisierte Projekte
- Anteil defekter oder blockierter Ziele
- Meldungen pro 1.000 Projektaufrufe
- Bearbeitungszeit von Meldungen
- Anteil ungültigen Traffics
- Werbekunden-Retention
- Erlös pro 1.000 qualifizierten Besuchen

## 22. Empfohlene Umsetzungsphasen

### Phase 1: Sicheres Publishing

- Konten und Altersgruppen
- Creator-Profile
- Minecraft- und Roblox-Projekte
- Releases und externe Ziele
- URL-Prüfung
- Moderation
- öffentliche Suche und Projektseiten

### Phase 2: Discovery und Interaktion

- Interessen-Onboarding
- personalisierter Feed
- Folgen und Speichern
- einfache Bewertungen
- „Nicht interessiert“
- Benachrichtigungszentrum

### Phase 3: Creator-Aktivität

- neue Releases und Changelogs
- YouTube-/Twitch-Verknüpfung
- Live-Erkennung
- Web Push
- Creator-Statistiken

### Phase 4: Monetarisierung

- Verifizierung volljähriger Creator
- manuell betreute Werbekampagnen
- Revenue Share
- Fraud-Prüfung
- Einnahmensperrfrist
- Auszahlungen

## 23. Offene Entscheidungen

### Kritisch vor Abschluss des MVP-PRD

1. Pilotland und anwendbare rechtliche Rahmenbedingungen
2. Veröffentlichungsprüfung und Moderations-SLA
3. Verfahren zur Creator-Verifizierung
4. genaue Mindestalter- und Einwilligungslogik je Startmarkt
5. erlaubte und verbotene Roblox-Projekttypen
6. Definition der zulässigen externen Dateitypen

### Vor Monetarisierung

1. Creator-/Plattform-Revenue-Split
2. Werbeformate und Platzierungen
3. Abrechnungsmodell für Werbekunden
4. Auszahlungsanbieter, Währungen und Mindestbeträge
5. Steuer- und Identitätsprüfung
6. Kampagnenreporting und Attributionsmodell

### Später

1. Zeitpunkt und Umfang des eigenen Datei-Hostings
2. native Mobile Apps
3. Freitextrezensionen oder Kommentare
4. Self-Service-Werbeplattform
5. weitere Spiele und Content-Kategorien

## 24. Nächstes empfohlenes Ergebnis

Auf Basis dieses Dokuments sollte als Nächstes ein **MVP Product Requirements Document (PRD)** erstellt werden. Es sollte enthalten:

- Personas und Jobs-to-be-Done
- End-to-End User Journeys
- priorisierte Epics und User Stories
- funktionale Anforderungen
- Rollen- und Berechtigungsmatrix
- Akzeptanzkriterien
- Moderations- und Sicherheitsabläufe
- Analytics Events
- MVP-Abnahmekriterien
- Abhängigkeiten und offene Produktfragen

Vor dem vollständigen PRD können Veröffentlichungsprüfung, Creator-Verifizierung und Pilotmarkt zunächst als explizite Annahmen geführt werden.
