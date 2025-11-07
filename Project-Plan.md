Ursprungsaufgabe:
Sie sind Auszubildende bzw. Auszubildender der ChangeIT GmbH und derzeit in der Abteilung Anwendungsentwicklung eingesetzt. Von Ihrer Vorgesetzten erhalten Sie die Aufgabe, für einen Kunden eine Projektverwaltung (To-do App) zu entwickeln. Die Anwendung sollte es ermöglichen, Mitarbeitern diverse Aufgaben (To-dos) zuzuordnen und diese mit Prioritäten zu versehen. Die Aufgaben sind unterschiedlichen Projekten zugewiesen.

Meldet sich ein Mitarbeiter an dem System an, so sieht er eine Liste der zu bearbeitenden Aufgaben. Über eine Detailanzeige kann er hierbei erkennen, zu welchem Projekt die Aufgabe gehört, welche Priorität sie hat und wer noch daran mitarbeitet. Er hat ferner die Möglichkeit, die Aufgabe als erledigt zu markieren.

Abteilungsleiter und Mitarbeiter haben die gleichen Berechtigungen. Wenn sich ein Abteilungsleiter am System anmeldet, so hat er darüber hinaus die Möglichkeit, die Aufgaben seiner Mitarbeiter zu sehen, sowie neue Aufgabe zu erstellen und diesen Prioritäten sowie Projekten zuzuweisen. Ferner verfügt er über die Berechtigung, neue Projekte anzulegen.

Der Administrator des Systems kann Benutzerkonten anlegen und für diese Rollen vergeben und ändern. Das Backend steht in Form einer REST-API zur Verfügung (Dokumentation der REST-API). In diesem Lernfeld soll für diese Anwendung ein Frontend in Form einer Webseite und einer Android-App entwickelt werden.



---

Ziel der Anwendung:
API für TODO APP

---

Geplante Struktur für die Datenbank:

```json
{
    "changeit-todo-app-db": {
        "user": 
        { 
            "id": "uuid PRIMARY KEY", 
            "name": "STRING(32) NOT NULL UNIQUE", 
            "email": "STRING(128) NOT NULL UNIQUE", "password": "STRING(255) NOT NULL", 
            "is_password_temporary": "boolean NOT NULL", 
            "active": "boolean NOT NULL", 
            "temporary_password_created_at": "DateTimeImmutable NOT NULL", 
            "created_at": "DateTimeImmutable NOT NULL", 
            "last_login_at": "datetime NULL"
        },
        "user_to_role": {
            "user_id": "uuid NOT NULL",
            "role_id": "uuid NOT NULL",
            "PRIMARY": "PRIMARY KEY (user_id, role_id)"
        },
        "role": {
            "id": "uuid PRIMARY KEY",
            "perm_can_create_user": "boolean NOT NULL",
            "perm_can_edit_user": "boolean NOT NULL",
            "perm_can_read_user": "boolean NOT NULL",
            "perm_can_delete_user": "boolean NOT NULL",
            "perm_can_create_tasks": "boolean NOT NULL",
            "perm_can_edit_tasks": "boolean NOT NULL",
            "perm_can_read_all_tasks": "boolean NOT NULL",
            "perm_can_delete_tasks": "boolean NOT NULL",
            "perm_can_assign_tasks_to_user": "boolean NOT NULL",
            "perm_can_assign_tasks_to_project": "boolean NOT NULL",
            "perm_can_create_projects": "boolean NOT NULL",
            "perm_can_edit_projects": "boolean NOT NULL",
            "perm_can_read_projects": "boolean NOT NULL",
            "perm_can_delete_projects": "boolean NOT NULL"
        },
        "project": {
            "id": "uuid PRIMARY KEY",
            "name": "STRING(255) NOT NULL UNIQUE",
            "description": "TEXT NULL",
            "created_by_user_id": "uuid NOT NULL",
            "created_at": "DateTimeImmutable NOT NULL"
        },
        "task": {
            "id": "uuid PRIMARY KEY",
            "title": "STRING(255) NOT NULL",
            "description": "TEXT NULL",
            "status": "STRING(50) NOT NULL",
            "priority": "STRING(50) NOT NULL",
            "due_date": "TIMESTAMP NULL",
            "created_by_user_id": "uuid NOT NULL",
            "assigned_to_user_id": "uuid NULL",
            "project_id": "uuid NULL",
            "created_at": "DateTimeImmutable NOT NULL",
            "updated_at": "TIMESTAMP NULL"
        },
        "image": {
            "id": "uuid PRIMARY KEY",
            "file_type": "STRING(10) NOT NULL",
            "file_size": "INT NOT NULL",
            "uploaded_at": "DateTimeImmutable NOT NULL",
            "uploaded_by_user_id": "uuid NOT NULL",
            "project_id": "uuid NULL",
            "task_id": "uuid NULL",
            "user_id": "uuid NULL",
            "type": "STRING(50) NOT NULL"
        },
        "logs": {
            "id": "uuid PRIMARY KEY",
            "action": "STRING(255) NOT NULL",
            "performed_by_user_id": "uuid NOT NULL",
            "performed_at": "DateTimeImmutable NOT NULL",
            "details": "TEXT NULL"
        },
        "password_reset_tokens": {
            "id": "uuid PRIMARY KEY",
            "user_id": "uuid NOT NULL",
            "token_digest": "STRING(64) NOT NULL UNIQUE",
            "expires_at": "DateTimeImmutable NOT NULL",
            "created_at": "DateTimeImmutable NOT NULL",
            "used_at": "DateTimeImmutable NULL",
            "on_delete": "CASCADE"
        },
        "app_config": {
            "id": "uuid PRIMARY KEY",
            "allowed_email_domains": "JSON NOT NULL"
        }
    }
}
```

Wichtige Infos zu Images:

* Ein Image kann entweder einem Projekt, einer Task oder einem User zugeordnet sein.
* Der Typ des Images wird im Feld `type` gespeichert: `profile_picture`, `project_image`, `task_image`.
* Die Zuordnungen werden über optionale Foreign Keys (`project_id`, `task_id`, `user_id`) abgebildet; `uploaded_by_user_id` ist Pflicht.
* Die Logs-Tabelle speichert nur sinnvolle Aktionen, wie Löschen, Erstellen, Updaten von Usern, Projekten, Tasks und Änderungen an Rollen etc.
* Die Logs-Tabelle speichert keine Login-Versuche oder ähnliches und darf niemals mehr als 5 GB groß werden. Ältere Logs müssen regelmäßig und automatisch gelöscht werden.

---

# Endpunkte

Mit welcher Rolle welcher Endpunkt aufgerufen werden kann, kann den Rollen entnommen werden.
Jeder Daten-Endpunkt besitzt ein `/api/info/*`-Gegenstück, das neben den erwarteten Feldern auch alle potenziellen Fehlercodes (z. B. `USED_ACCOUNT_IS_INACTIVE`, `EMAIL_ISNT_COMPANY_EMAIL`, `USERNAME_ALREADY_IN_USE`) beschreibt.
Nutzer, die sich selbst zum Ziel haben, haben natürlich erhöhte Rechte im üblichen Rahmen.

Welche Berechtigungen ein Nutzer hat, wird über eine eigene Funktion geprüft.
`GetUserPermissions(user_id)`, welche dann einfach jede Rolle des Users durchgeht und die Permissions zusammenfasst. Wenn eine Rolle, die er hat, bei einer Permission `true` hat, dann hat er diese Permission.
Dafür braucht es natürlich auch einen Endpunkt, der die Permissions des Nutzers zurückgibt, damit ich diese Zusammenfassungslogik nicht auch im Frontend brauche.
`GET /api/user/permissions` – gibt die Permissions des eingeloggten Users zurück.

---

## AUTH

* `POST /api/auth/login` – im Body `email` und `password`, gibt einen Token zurück.
* `POST /api/auth/refresh` – nimmt einen gültigen Refresh-Token entgegen und gibt ein neues Access-Token zurück.
* `POST /api/auth/logout` – invalidiert/entfernt die aktuelle Session bzw. den Refresh-Token.
* `POST /api/auth/change-password` – für eingeloggte Nutzer: `{ current_password, new_password }`.
* `POST /api/auth/reset-password/confirm` – `{ token, new_password }` – Abschluss des Reset-Flows aus Forgot/Reset.
* `POST /api/auth/register` – legt einen neuen, zunächst inaktiven Account mit Firmen-E-Mail an.

---

## User

* `GET /api/user` – gibt den eingeloggten User zurück, aber natürlich niemals `temporary_password_created_at`, `is_password_temporary`, `password`.
* `GET /api/user/{id}` – gibt den User mit der angegebenen ID zurück (ohne sensible Felder wie oben).
* `GET /api/user/obfuscated-email/{id}` – liefert eine maskierte Variante der E-Mail (z. B. `max...@g...`) zur Anzeige im Reset-Flow.
* `GET /api/user/by-role/{role_id}` – gibt alle User mit der angegebenen Rolle zurück.
* `POST /api/user/reset-password` – sendet dem eingeloggten User eine E-Mail mit einem Link zum Zurücksetzen des Passworts.
* `POST /api/user/verify-email-for-password-reset/{id}` – nimmt die vollständige E-Mail entgegen, vergleicht sie mit dem Benutzerkonto und versendet anschließend den Reset-Link.
* `POST /api/user` – erstellt einen neuen User.
* `PATCH /api/user` – aktualisiert eigene Profildaten (z. B. `name`); Felder serverseitig whitelisten.
* `PATCH /api/user/{id}` – Admin-Update für Nutzer (Permissions erforderlich).
* `POST /api/user/{id}/deactivate` – setzt `active = false`.
* `POST /api/user/{id}/reactivate` – setzt `active = true`.
* `GET /api/user/{id}/tasks` – listet Tasks des Users (unterstützt die allgemeinen Listen-Filter).
* `GET /api/user/{id}/projects` – listet Projekte, die der User erstellt hat (oder später Mitglied ist).

---

## role

* `GET /api/role/by-user/{user_id}` – gibt Rollen eines Users zurück.
* `PATCH /api/role/by-user/{user_id}` – um die Rollen eines Users anzupassen, im Body dann die neuen Rollen-IDs als Array; fehlende Berechtigungen bleiben wie sie sind.
* `GET /api/role/list`
* **Rollenverwaltung (optional, falls gewünscht):**

  * `GET /api/role/{id}`
  * `POST /api/role`
  * `PATCH /api/role/{id}`
  * `DELETE /api/role/{id}` (sofern nicht mehr zugewiesen)
  * `GET /api/permission/catalog` – Liste aller unterstützten Permission-Keys (für Admin-UIs).

---

## project

* `GET /api/project/{id}` – gibt das Projekt mit der angegebenen ID zurück.
* `POST /api/project` – erstellt ein neues Projekt.
* `PATCH /api/project/{id}` – passt das Projekt mit der angegebenen ID an.
* `GET /api/project/list`
* `GET /api/project/{id}/tasks` – listet Tasks eines Projekts (mit Listen-Parametern/Filtern).

---

## task

* `GET /api/task/{id}` – gibt die Task mit der angegebenen ID zurück.
* `POST /api/task` – erstellt eine neue Task.
* `PATCH /api/task/{id}` – passt die Task mit der angegebenen ID an.
* `GET /api/task/list`


* **Aktionsendpunkte:**
  * `POST /api/task/{id}/assign-user` – Body: `{ user_id }`.
  * `POST /api/task/{id}/unassign-user`
  * `POST /api/task/{id}/move-to-project` – Body: `{ project_id }`.
  * `POST /api/task/{id}/status` – Body: `{ status }`.

---

## Images

* `POST /api/image` – `multipart/form-data` Upload (`file`, `type`, **genau eine** von: `project_id` **oder** `task_id` **oder** `user_id`).
* `GET /api/image/{id}` – liefert Datei/Stream (Header `Content-Disposition` inline/attachment).
* `PATCH /api/image/{id}` – `type` ändern **oder** Zuordnung (immer nur eine Referenz).
* `GET /api/image/list` – unterstützt Filter: `?user_id=` **oder** `?project_id=` **oder** `?task_id=` (exakt einer erlaubt).
* `DELETE /api/image/{id}` – löscht das Image mit der angegebenen ID.

---

## Löschvorgänge

Wenn ein Projekt oder eine Task gelöscht wird, muss jedes Mal geprüft werden, ob es Bilder zu löschen gibt, die diesem User, Projekt oder Task zugeordnet sind. Diese müssen dann auch gelöscht werden.
Bei User muss das nicht geschehen, da User nicht gelöscht werden können; sie können nur auf `inactive` gesetzt werden.

* `DELETE /api/task/{id}` – löscht die Task mit der angegebenen ID.
* `DELETE /api/project/{id}` – löscht das Projekt mit der angegebenen ID.

---

## Logs

* `GET /api/logs/list`
* `GET /api/logs/{id}`
* `GET /api/logs/stats` – z. B. `{ total_count, approx_size_bytes, last_retention_run_at }`.

---

## Listen

**Parameter (global für `*/list`-Endpunkte):**

* `offset`, `limit` – Standard: `offset=0`, `limit=100`
* `sort_by` – eines der in der jeweiligen Liste ausgegebenen Felder
* `direction` – `asc` (Standard) oder `desc`

**Zusätzliche optionale Filter (wo sinnvoll):**

* `status`, `priority`
* `project_id`, `assigned_to_user_id`, `created_by_user_id`
* `due_date_from`, `due_date_to` (ISO-8601)
* `q` – einfacher Freitextfilter zusätzlich zur dedizierten Suche

Vorhandene Endpunkte für Listen:

* `GET /api/user/list` – Sonderfall: `password`, `temporary_password_created_at` und `is_password_temporary` werden nicht ausgegeben.
* `GET /api/role/list`
* `GET /api/project/list`
* `GET /api/task/list`
* `GET /api/logs/list`

---

## Suche

* `GET /api/search/{suchbegriff}` – sucht in allen **searchable** Entities in deren **searchable** Feldern; case-insensitive; Teiltreffer sind erlaubt.
  **Searchable Entities und Felder:**
* `user`: `name`, `email`
* `project`: `name`, `description`, `created_by_user_id`
* `task`: `title`, `description`, `status`, `priority`, `created_by_user_id`, `assigned_to_user_id`
* `logs`: `action`, `performed_by_user_id`, `details`

Spezifische Suche pro Entity:

* `GET /api/search/user/{suchbegriff}`
* `GET /api/search/project/{suchbegriff}`
* `GET /api/search/task/{suchbegriff}`
* `GET /api/search/logs/{suchbegriff}`

**Komplexe Suche (optional):**

* `POST /api/search` – Body: `{ entity?: 'user'|'project'|'task'|'logs', q: string, filters?: {...} }`

---

## System

* `GET /api/health`
* `GET /api/version`

---

# Info-Endpoints (Schemas & Limits für Vorvalidierung)

Für **jeden Endpunkt, der Daten erwartet** (z. B. `POST`, `PATCH`, Aktions-POSTs), existiert ein **gleichnamiger Info-Endpunkt** unter `/api/info/...` auf **demselben Pfad** (ohne Seiteneffekte).
Diese Info-Endpunkte liefern ein maschinenlesbares Objekt zur **Vorvalidierung im Frontend** (Typen, `required`, `nullable`, `maxLength`, `format`, erlaubte Werte/Beziehungen) sowie **optionale weiche Limits** (z. B. `max_file_size`). **Die echte Validierung passiert immer im Backend.**

**Beispiele (Ausschnitt):**

* `POST /api/project` → `POST /api/info/project`
  Beispiel-Antwort:

  ```json
  {
    "entity": "project",
    "action": "create",
    "fields": {
      "name": { "type": "string", "required": true, "nullable": false, "maxLength": 255, "unique": true },
      "description": { "type": "string", "required": false, "nullable": true },
      "created_by_user_id": { "type": "uuid", "required": true, "nullable": false }
    }
  }
  ```

* `PATCH /api/project/{id}` → `POST /api/info/project/{id}`
  (gleiches Schema, aber alle Felder optional; Backend definiert, welche Felder änderbar sind – Flag `writable: true|false` möglich)

* `POST /api/task` → `POST /api/info/task`

  ```json
  {
    "entity": "task",
    "action": "create",
    "fields": {
      "title": { "type": "string", "required": true, "nullable": false, "maxLength": 255 },
      "description": { "type": "string", "required": false, "nullable": true },
      "status": { "type": "string", "required": true, "nullable": false, "maxLength": 50 },
      "priority": { "type": "string", "required": true, "nullable": false, "maxLength": 50 },
      "due_date": { "type": "datetime", "required": false, "nullable": true, "format": "ISO-8601" },
      "created_by_user_id": { "type": "uuid", "required": true, "nullable": false },
      "assigned_to_user_id": { "type": "uuid", "required": false, "nullable": true },
      "project_id": { "type": "uuid", "required": false, "nullable": true }
    }
  }
  ```

* `POST /api/image` → `POST /api/info/image`

  ```json
  {
    "entity": "image",
    "action": "create",
    "fields": {
      "file": { "type": "file", "required": true, "nullable": false },
      "type": { "type": "string", "required": true, "nullable": false, "maxLength": 50 },
      "project_id": { "type": "uuid", "required": false, "nullable": true },
      "task_id": { "type": "uuid", "required": false, "nullable": true },
      "user_id": { "type": "uuid", "required": false, "nullable": true }
    },
    "relations": {
      "exactly_one_of": ["project_id", "task_id", "user_id"]
    },
    "soft_limits": {
      "max_file_size_bytes": null,
      "allowed_mime_types": null
    }
  }
  ```

* `POST /api/user` → `POST /api/info/user`

  ```json
  {
    "entity": "user",
    "action": "create",
    "fields": {
      "name": { "type": "string", "required": true, "nullable": false, "maxLength": 32, "unique": true },
      "email": { "type": "string", "required": true, "nullable": false, "maxLength": 128, "format": "email", "unique": true },
      "password": { "type": "string", "required": true, "nullable": false, "maxLength": 255 }
    }
  }
  ```

* `PATCH /api/user/{id}` → `POST /api/info/user/{id}`
  (nur die serverseitig freigegebenen Felder werden mit `writable: true` deklariert)

* `POST /api/task/{id}/assign-user` → `POST /api/info/task/{id}/assign-user`

  ```json
  {
    "entity": "task_assignment",
    "action": "assign",
    "fields": {
      "user_id": { "type": "uuid", "required": true, "nullable": false }
    }
  }
  ```

* `POST /api/auth/change-password` → `POST /api/info/auth/change-password`

  ```json
  {
    "entity": "auth_change_password",
    "action": "change",
    "fields": {
      "current_password": { "type": "string", "required": true, "nullable": false },
      "new_password": { "type": "string", "required": true, "nullable": false, "maxLength": 255 }
    }
  }
  ```

**Konventionen für Info-Endpunkte:**

* **Methode:** Immer `POST` (auch wenn der Daten-Endpunkt `PATCH` ist), damit ein Body für kontextuelle Parameter möglich ist (z. B. ID im Pfad zusätzlich zulässig).
* **Antwortfelder (Mindestumfang):**

  * `entity`, `action`
  * `fields`: Map je Feld mit `type`, `required`, `nullable`, optional `maxLength`, `format`, `unique`, `writable`
  * optional `relations` (z. B. `exactly_one_of`)
  * optional `soft_limits` (z. B. `max_file_size_bytes`, `allowed_mime_types`)
  * optional `examples` (Beispiel-Payloads)

---

# Einheitliches Fehler-/Antwortformat

**Alle Fehlerantworten** folgen **RFC 7807 (Problem Details)** und liefern zusätzlich einen **stabilen maschinenlesbaren Code** (englisch), damit das Frontend sprachspezifische Meldungen ausspielen kann.

**Beispiel (401 – Session abgelaufen):**

```json
{
  "type": "about:blank",
  "title": "Unauthorized",
  "status": 401,
  "detail": "Your session has expired.",
  "code": "SESSION_EXPIRED"
}
```

**Beispiel (403 – fehlende Berechtigung):**

```json
{
  "type": "about:blank",
  "title": "Forbidden",
  "status": 403,
  "detail": "You do not have permission to perform this action.",
  "code": "PERMISSION_DENIED"
}
```

**Beispiel (422 – Validierung):**

```json
{
  "type": "about:blank",
  "title": "Unprocessable Entity",
  "status": 422,
  "detail": "Validation failed.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "name": ["must not be empty", "max length is 255"],
    "email": ["must be a valid email"]
  }
}
```

**Empfohlene Fehlercodes (Auszug):**

* `SESSION_EXPIRED`, `TOKEN_INVALID`, `TOKEN_MISSING`
* `PERMISSION_DENIED`
* `RESOURCE_NOT_FOUND`
* `VALIDATION_ERROR`
* `CONFLICT` (z. B. Unique-Verletzung)
* `PAYLOAD_TOO_LARGE` (z. B. Image-Upload)
* `RATE_LIMITED`
* `INTERNAL_ERROR`

**Erfolgsantworten (Listen):**

```json
{
  "items": [ /* … */ ],
  "total": 123,
  "offset": 0,
  "limit": 100,
  "sort_by": "created_at",
  "direction": "asc"
}
```

**Erfolgsantworten (Einzelressource):**

```json
{
  "data": { /* objekt */ },
  "etag": "W/\"abc123\"",
  "last_modified": "2025-09-25T16:00:00Z"
}
```

> Hinweise:
> • Texte in `detail` sind **englisch**; das Frontend mappt `code` → lokalisierte Meldung.
> • `errors` (bei `VALIDATION_ERROR`) enthält Feld-fehlerlisten.
> • `etag`/`last_modified` sind optional und dienen u. a. für Optimistic Locking/Caching.

---

Damit hast du **für jeden datenentgegennahmenden Endpunkt** einen klaren `/api/info/*`-Counterpart zur **Frontend-Vorvalidierung**, plus ein **konsistentes Fehler-/Antwortformat**, ohne irgendetwas an deinem Datenbankschema zu verändern.
