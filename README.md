# 🌊 Mitig8

Mitig8 lets citizens report drainage blockages and flood risk with a photo and their location. A Gemma 4 multimodal model analyzes the photo, assigns a risk level, and pushes the report to a live admin dashboard so response teams (like NADMO) can triage and act — before a blocked gutter becomes a flooded neighborhood.

Designed for the African connectivity reality: it runs **fully offline, on-device**, with an optional cloud path for when connectivity is available.

---

## The Problem

Flooding in cities like Accra and Kumasi is frequently caused or worsened by blocked drains and gutters that go unreported until it's too late. Citizens see the warning signs — clogged gutters, choked culverts, standing water — long before a storm turns them into a flood. There's no fast, low-friction way for them to report it, and no automated way to triage which reports need urgent attention versus routine maintenance.

## The Solution

1. A citizen takes a photo of a drainage issue on their phone, adds a short description, and submits — geolocation is captured automatically.
2. **Gemma 4 (multimodal)** analyzes the photo alongside the description and classifies flood risk as **Low / Medium / High**, with a short explanation grounded in what's visible in the image.
3. The report — image, location, AI risk assessment — is saved and instantly available on an **admin dashboard** with a **Leaflet map**, pinned by location and color-coded by risk.
4. Admins can review AI reasoning, triage reports, and track resolution — with a computed **District Safety Score** and an AI-generated operational summary of the current situation.


## Architecture

Mitig8 is **offline-first, cloud-optional** — it's designed around the constraint that connectivity in the field can't be assumed, and gracefully upgrades when it's available.

```
Citizen Webapp (photo + geolocation + description)
            │
            ▼
      PHP submission API
            │
            ├── Offline path: local Gemma 4 (E2B) via Ollama
            │       → runs entirely on-device, no internet required
            │
            └── Cloud path (optional): Gemma 4 via Google Gemini API
                    → used when connectivity is available
            │
            ▼
       MySQL database
            │
            ▼
   Admin Dashboard (Leaflet map, risk-coded pins,
   AI-generated operational summary, safety score)
```

Both AI paths are held to the same contract: a structured `{"risk_level": "High"|"Medium"|"Low", "reasoning": "..."}` response, and a fail-soft **"Unreviewed"** state (never a false "Low") if AI analysis is unavailable — so a failed AI call is never mistaken for a genuine low-risk report on the dashboard.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, Geolocation API |
| Backend | PHP |
| Database | MySQL |
| AI (offline) | Gemma 4 (E2B) via [Ollama](https://ollama.com) |
| AI (cloud, optional) | Gemma 4 via [Google Gemini API](https://aistudio.google.com) |
| Map | [Leaflet.js](https://leafletjs.com) |
| Local dev environment | XAMPP |




## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org) (or any PHP 8+ / MySQL environment)
- [Ollama](https://ollama.com) installed locally, with the Gemma 4 E2B model pulled:
  ```bash
  ollama pull gemma4:e2b
  ```
- *(Optional, for the cloud path)* A free API key from [Google AI Studio](https://aistudio.google.com/apikey)

### Setup

1. Clone the repo into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/profyatey/mitig8_gemma4.git
   ```
2. Create the MySQL database and import the schema:
   ```bash
   mysql -u root -p < database/mitig8.sql
   ```
3. Configure your credentials:
   - `config/db.php` — your MySQL connection details
   - `config/ollama.php` — your local Ollama endpoint (default: `http://localhost:11434/api/chat`) and model name (`gemma4:e2b`)
   - `config/gemini.php` *(optional)* — your Gemini API key and model (`gemma-4-26b-a4b-it`)
4. Start Apache and MySQL in XAMPP.
5. Make sure Ollama is running:
   ```bash
   ollama list
   ```
6. Visit `http://localhost/mitig8/index.php` to submit a report, and `http://localhost/mitig8/dashboard/` for the admin view.

7. For Online Hosted Version Visit (https://mitig8.freedev.app/index.php) to submit a report , and (https://mitig8.freedev.app/dashboard)
---

## Project Structure


mitig8/
├── index.php                  # Citizen report submission form
├── api/
│   └── submit_report.php      # Handles upload, AI analysis, DB insert
├── config/
│   ├── db.php                 # MySQL connection
│   ├── ollama.php             # Local Gemma 4 (offline) config
│   └── gemini.php             # Gemini API (cloud) config
├── dashboard/
│   ├── index.php              # Admin dashboard + Leaflet map
│   ├── ai_insight.php         # AI-generated operational summary + safety score
│   └── includes/              # Shared header/sidebar/footer
├── uploads/                   # Citizen-submitted photos
└── database/
    └── schema.sql



## Built For

**Build with Gemma: Ghana** — a Gemma 4 community hackathon hosted on Kaggle, powered by Google DeepMind.

## License

MIT — see [LICENSE](LICENSE) for details.
