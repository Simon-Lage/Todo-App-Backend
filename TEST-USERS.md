# Test-Benutzer für die Todo-App

## Feste Test-Accounts (nach Seed-Command)

Diese 4 Test-Benutzer werden **immer** mit denselben Daten erstellt:

### 🔴 **Administrator**
- **E-Mail:** `admin@changeit.test`
- **Passwort:** `123`
- **Rolle:** Admin
- **Berechtigungen:** ALLE (User, Rollen, Tasks, Projekte - vollständige CRUD)

### 🟡 **Abteilungsleiter (Teamlead) #1**
- **E-Mail:** `teamlead@changeit.test`
- **Passwort:** `123`
- **Rolle:** Teamlead
- **Berechtigungen:**
  - ✅ User lesen
  - ✅ Rollen lesen
  - ✅ Tasks erstellen, bearbeiten, alle lesen, zuweisen
  - ✅ Projekte erstellen, bearbeiten, lesen
  - ❌ Keine Admin-Rechte (User/Rollen verwalten)
  - ❌ Keine Lösch-Rechte

### 🟡 **Abteilungsleiter (Teamlead) #2**
- **E-Mail:** `simon.lage.email@gmail.com`
- **Passwort:** `123`
- **Rolle:** Teamlead
- **Berechtigungen:**
  - ✅ User lesen
  - ✅ Rollen lesen
  - ✅ Tasks erstellen, bearbeiten, alle lesen, zuweisen
  - ✅ Projekte erstellen, bearbeiten, lesen
  - ❌ Keine Admin-Rechte (User/Rollen verwalten)
  - ❌ Keine Lösch-Rechte

### 🟢 **Mitarbeiter (Staff)**
- **E-Mail:** `staff@changeit.test`
- **Passwort:** `123`
- **Rolle:** Staff
- **Berechtigungen:**
  - ✅ Tasks bearbeiten (nur eigene)
  - ✅ Projekte lesen
  - ❌ Keine Tasks erstellen
  - ❌ Keine Projekte erstellen
  - ❌ Keine Admin-Rechte

---

## Zusätzliche Random-User

Neben den 4 festen Test-Accounts werden auch generiert:
- **1 weiterer Admin** (zufälliger Name)
- **9 weitere Teamleads** (zufällige Namen)
- **99 weitere Staff-Member** (zufällige Namen)

**Alle haben das gleiche Passwort:** `123`

**E-Mail-Format:** `{vorname}.{nachname}.{rolle}{nummer}@changeit.test`

Beispiele:
- `alex.anderson.a02@changeit.test` (Admin #2)
- `sam.bennett.t02@changeit.test` (Teamlead #2)
- `jamie.campbell.s02@changeit.test` (Staff #2)

---

## Daten neu generieren

```bash
cd Todo-App-Backend
docker compose exec php bin/console app:dev:seed-random-data --purge
```

**Achtung:** Der `--purge` Flag löscht ALLE vorhandenen Daten!

---

## Schnelltest (für CI/CD)

Alternativ zum Seed-Command gibt es auch einen einzelnen Test-Admin:

```bash
docker compose exec php bin/console app:test:create-user
```

- **E-Mail:** `admin@changeit.de`
- **Passwort:** `password123`
- **Rolle:** Administrator (alle Berechtigungen)

