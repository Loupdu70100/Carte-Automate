const express = require('express');
const mysql = require('mysql2');
const axios = require('axios');
const xml2js = require('xml2js');
const dayjs = require('dayjs');
const cors = require('cors');
const http = require('http');
const { time } = require('console');

const app = express();
const port = 3000;
app.use(express.json());
app.use(cors());

const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'Zx23-Zx81',
    database: 'ubmap'
});

app.get('/api/alerts', (req, res) => {
    const query = 'SELECT * FROM alertes ORDER BY datetime DESC';
    db.execute(query, (err, results) => {
        if (err) return res.status(500).send('Erreur serveur');
        res.status(200).json(results);
    });
});

app.patch('/api/alerts/:id', (req, res) => {
    const alertId = req.params.id;
    const { commentaire, status } = req.body;
    const query = `UPDATE alertes SET commentaire = ?, status = ? WHERE id = ?`;
    const safeCommentaire = typeof commentaire !== 'undefined' ? commentaire : null;
    const safeStatus = typeof status !== 'undefined' ? status : null;

    db.execute(query, [safeCommentaire, safeStatus, alertId], (err) => {
        if (err) console.error('Erreur lors de la création de lalerte:', err);
        else console.log('Alerte modifier:', alertId);
    });
});

function getPoints(callback) {
    db.execute('SELECT id,model, ip_address, port, identifiant, mdp FROM points', (err, results) => {
        if (err) return callback(err);
        callback(null, results);
    });
}

function fetchXML(ip, port, user, pass, callback) {
    axios.get(`http://${ip}:${port}/status.xml?a=${user}:${pass}`)
        .then(response => callback(null, response.data))
        .catch(error => callback(error));
}

function insertMesure(id, tens, temp, datetime, callback) {
    const query = 'INSERT INTO mesures (id_point, tens, temp, heure) VALUES (?, ?, ?, ?)';
    db.execute(query, [id, tens, temp, datetime], callback);
}

function handleThresholds(id_point, tens, temp) {
    const seuil_temp_max = 60;
    const seuil_tens_min = 22;
    const seuil_tens_max = 29;
    if (temp > seuil_temp_max) createAlert(`Température trop élevée (${temp} °C) pour le point ${id_point}`);
    if (tens < seuil_tens_min || tens > seuil_tens_max) createAlert(`Tension hors limites (${tens} V) pour le point ${id_point}`);
}

function createAlert(motif) {
    const query = 'INSERT INTO alertes (motif, status) VALUES (?, "open")';
    db.execute(query, [motif], (err) => {
        if (err)
            console.error('Erreur lors de la création de lalerte:', err);
        else 
            console.log('Alerte créée:', motif);
    });
}

function ping(ip, port = 80, timeout = 1000, callback) {
    const req = http.get({ host: ip, port, timeout }, res => {
        res.resume();
        callback(true);
    });
    req.on('error', () => callback(false));
    req.on('timeout', () => {
        req.abort();
        callback(false);
    });
}

function processPoints() {
    getPoints((err, points) => {
        if (err) return console.error('Erreur getPoints:', err);

        for (let i = 0; i < points.length; i++) {
            setTimeout(() => {
                console.log("Après 2 secondes");
              }, 10000);
            const { id,model, ip_address, port, identifiant, mdp } = points[i];
            if (!ip_address) continue;

            ping(ip_address, port, 1000, (alive) => {
                if (alive) {
                    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
                    db.execute('UPDATE points SET last_ping_routeur = ? WHERE id = ?', [now, id]);
                }
            });
            if(model == "ubbat"){
                fetchXML(ip_address, port, identifiant, mdp, (err, xmlData) => {
                    if (err || !xmlData) {
                        console.log(`Automate ${ip_address} inaccessible.`);
                        return;
                    }

                    xml2js.parseString(xmlData, (err, result) => {
                        if (err) return console.error(`Erreur parsing XML pour ${id}:`, err);
                        try {
                            const temp = parseFloat(result.Monitor.Temperature1[0].replace('°C', '').trim());
                            const tens = parseFloat(result.Monitor.AnalogInput1[0].replace('V', '').trim());
                            const datetime = dayjs().format('YYYY-MM-DD HH:mm:ss');

                            console.log(`[DEBUG] Données brutes pour id_point ${id}:`, temp, tens, datetime);

                            insertMesure(id, tens, temp, datetime, (err) => {
                            if (err) return console.error(`Erreur insertion mesure ${id}:`, err);
                                console.log(`Données insérées pour id_point ${id}`);
                                console.log(`Insertion : [${id}, ${tens}, ${temp}, ${datetime}]`);
                                handleThresholds(id, tens, temp);
                            });
                        } catch (err) {
                            console.error(`Erreur traitement point ${id}:`, err);
                        }
                    });
                });
            }else{
                console.log("Urbanbox 230V Donc on ne recupere pas les info");
            }    
        }
    });
}

setInterval(processPoints, 3600000);
app.listen(port, () => {
    console.log(`Serveur démarré sur http://localhost:${port}`);
});
