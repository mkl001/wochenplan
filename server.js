const express = require('express');
const fs      = require('fs');
const path    = require('path');

const app      = express();
const PORT     = process.env.PORT || 3000;
const DATA_DIR = path.join(__dirname, 'data');

// Ensure data directory exists
if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });

app.use(express.json());
app.use(express.static(__dirname)); // serve index.html from root

// ─── Helpers ──────────────────────────────────────────────────────────────────
function read(file) {
  const p = path.join(DATA_DIR, file);
  if (!fs.existsSync(p)) return null;
  try { return JSON.parse(fs.readFileSync(p, 'utf8')); } catch { return null; }
}
function write(file, data) {
  fs.writeFileSync(path.join(DATA_DIR, file), JSON.stringify(data, null, 2));
}

// ─── WEIGHTS ──────────────────────────────────────────────────────────────────
app.get('/api/weights', (_req, res) => {
  const d = read('weights.json') || { entries: [] };
  res.json(d.entries);
});

app.post('/api/weights', (req, res) => {
  const { date, kg } = req.body;
  if (!date || typeof kg !== 'number') return res.status(400).json({ error: 'date + kg required' });
  const d = read('weights.json') || { entries: [] };
  d.entries = d.entries.filter(e => e.date !== date);
  d.entries.push({ date, kg, savedAt: new Date().toISOString() });
  d.entries.sort((a, b) => a.date.localeCompare(b.date));
  write('weights.json', d);
  res.json({ ok: true });
});

app.delete('/api/weights/:date', (req, res) => {
  const d = read('weights.json') || { entries: [] };
  d.entries = d.entries.filter(e => e.date !== req.params.date);
  write('weights.json', d);
  res.json({ ok: true });
});

// ─── WATER ────────────────────────────────────────────────────────────────────
app.get('/api/water', (_req, res) => {
  res.json(read('water.json') || {});
});

app.get('/api/water/:date', (req, res) => {
  const d = read('water.json') || {};
  res.json({ date: req.params.date, count: d[req.params.date] || 0 });
});

app.post('/api/water', (req, res) => {
  const { date, count } = req.body;
  if (!date || typeof count !== 'number') return res.status(400).json({ error: 'date + count required' });
  const d = read('water.json') || {};
  d[date] = count;
  write('water.json', d);
  res.json({ ok: true, date, count });
});

// ─── TRAINING CHECK-INS ───────────────────────────────────────────────────────
// A check-in: { date, type: 'training'|'walk'|'row'|'meal_prep', done: bool, note? }
app.get('/api/checkins', (_req, res) => {
  const d = read('checkins.json') || { entries: [] };
  res.json(d.entries);
});

app.post('/api/checkins', (req, res) => {
  const { date, type, done, note } = req.body;
  if (!date || !type) return res.status(400).json({ error: 'date + type required' });
  const d = read('checkins.json') || { entries: [] };
  const idx = d.entries.findIndex(e => e.date === date && e.type === type);
  const entry = { date, type, done: !!done, note: note || '', updatedAt: new Date().toISOString() };
  if (idx >= 0) d.entries[idx] = entry; else d.entries.push(entry);
  d.entries.sort((a, b) => b.date.localeCompare(a.date));
  write('checkins.json', d);
  res.json({ ok: true, entry });
});

// ─── NOTES ────────────────────────────────────────────────────────────────────
app.get('/api/notes', (_req, res) => {
  const d = read('notes.json') || { entries: [] };
  res.json(d.entries);
});

app.post('/api/notes', (req, res) => {
  const { text, date } = req.body;
  if (!text) return res.status(400).json({ error: 'text required' });
  const d = read('notes.json') || { entries: [] };
  d.entries.unshift({
    id: Date.now(),
    text,
    date: date || new Date().toISOString().slice(0, 10),
    createdAt: new Date().toISOString()
  });
  d.entries = d.entries.slice(0, 500);
  write('notes.json', d);
  res.json({ ok: true });
});

app.delete('/api/notes/:id', (req, res) => {
  const d = read('notes.json') || { entries: [] };
  d.entries = d.entries.filter(e => e.id !== Number(req.params.id));
  write('notes.json', d);
  res.json({ ok: true });
});

// ─── STATUS ───────────────────────────────────────────────────────────────────
app.get('/api/status', (_req, res) => {
  res.json({ ok: true, version: '2.0.0', time: new Date().toISOString() });
});

// ─── START ────────────────────────────────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`\n✅  Wochenplan läuft  →  http://localhost:${PORT}`);
  console.log(`📁  Daten:            →  ${DATA_DIR}\n`);
});
