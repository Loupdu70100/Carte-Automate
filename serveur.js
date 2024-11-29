const express = require('express');
const mysql = require('mysql2');
const axios = require('axios');
const xml2js = require('xml2js');

const app = express();
const port = 3000;
app.use(express.json());

// Configuration de la base de données MySQL
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '', // Remplacez par votre mot de passe MySQL
    database: 'ubmap' // Remplacez par le nom de votre base de données
});

// === Routes REST ===
const cors = require('cors');

// Autoriser toutes les origines (ou spécifiez les domaines que vous souhaitez autoriser)
app.use(cors());


// 1. Récupérer toutes les alertes
app.get('/api/alerts', (req, res) => {
    const query = 'SELECT * FROM alertes ORDER BY datetime DESC';
    db.execute(query, (err, results) => {
        if (err) {
            console.error('Erreur lors de la récupération des alertes:', err);
            res.status(500).send('Erreur serveur');
        } else {
            res.status(200).json(results);
        }
    });
});

// 2. Mettre à jour une alerte (commentaire ou statut)
// Exemple de fonction de mise à jour de l'alerte
app.patch('/api/alerts/:id', (req, res) => {
    const alertId = req.params.id;
    const { commentaire, status } = req.body;

    // Remplacer undefined par null
    const safeCommentaire = commentaire === undefined ? null : commentaire;
    const safeStatus = status === undefined ? null : status;

    const query = `
        UPDATE alertes 
        SET commentaire = ?, status = ?
        WHERE id = ?;
    `;

    db.execute(query, [safeCommentaire, safeStatus, alertId], (err, result) => {
        if (err) {
            console.error('Erreur de mise à jour:', err);
            return res.status(500).send('Erreur de mise à jour de l\'alerte');
        }

        res.status(200).send('Alerte mise à jour');
    });
});


// 3. Supprimer les alertes clôturées
app.delete('/api/alerts', (req, res) => {
    const query = 'DELETE FROM alertes WHERE status = "closed"';
    db.execute(query, (err, result) => {
        if (err) {
            console.error('Erreur lors de la suppression des alertes clôturées:', err);
            res.status(500).send('Erreur serveur');
        } else {
            console.log('Alertes clôturées supprimées');
            res.status(200).send('Alertes clôturées supprimées');
        }
    });
});

// === Gestion XML et Insertion des Données ===
function dropalert() {
    return new Promise((resolve, reject) => {
        const select = 'SELECT id, status FROM alertes WHERE status = "closed"';  // Sélectionner seulement les alertes fermées
        db.execute(select, (err, results) => {
            if (err) {
                console.error('Erreur lors de la sélection des alertes fermées:', err);
                return reject(err);  // Retourner l'erreur si la sélection échoue
            }

            // Si nous avons des alertes fermées, on les supprime
            if (results.length > 0) {
                // Parcours des alertes et suppression de celles qui sont fermées
                results.forEach(alert => {
                    const drop = 'DELETE FROM alertes WHERE id = ?';  // Utilisation de `?` pour sécuriser la requête
                    db.execute(drop, [alert.id], (err, res) => {
                        if (err) {
                            console.error('Erreur lors de la suppression de l\'alerte:', err);
                            return reject(err);  // Retourner l'erreur si la suppression échoue
                        } else {
                            console.log(`Alerte avec ID ${alert.id} supprimée`);
                        }
                    });
                });
                resolve('Alertes fermées traitées');
            } else {
                console.log('Aucune alerte fermée à supprimer');
                resolve('Aucune alerte fermée à supprimer');
            }
        });
    });
}


// Fonction pour récupérer les points
function getPoints() {
    return new Promise((resolve, reject) => {
        const query = 'SELECT id, ip_address FROM points';
        db.execute(query, (err, results) => {
            if (err) {
                console.error('Erreur lors de la récupération des points:', err);
                reject(err);
            } else {
                resolve(results);
            }
        });
    });
}

// Récupérer le fichier XML d’un automate
async function fetchXMLFromIP(ip) {
    try {
        const response = await axios.get(`http://${ip}:25001/status.xml?a=admin:Zx23-Zx81`);
        return response.data;
    } catch (error) {
        console.error(`Erreur de récupération du fichier XML depuis ${ip}:`, error);
        return null;
    }
}

// Insérer les données depuis le fichier XML
async function insertDataFromXML() {
    try {
        const points = await getPoints();
        for (const point of points) {
            const { id, ip_address } = point;
            const xmlData = await fetchXMLFromIP(ip_address);

            if (!xmlData) {
                console.log(`Automate ${ip_address} inaccessible.`);
                continue;
            }

            xml2js.parseString(xmlData, (err, result) => {
                if (err) {
                    console.error('Erreur lors du parsing XML:', err);
                    return;
                }

                const temp = parseFloat(result.Monitor.Temperature1[0].replace('°C', '').trim());
                const tens = parseFloat(result.Monitor.AnalogInput1[0].replace('V', '').trim());
                const datetime = new Date().toISOString().slice(0, 19).replace('T', ' ');

                const query = 'INSERT INTO mesures (id_point, tens, temp, heure) VALUES (?, ?, ?, ?)';
                db.execute(query, [id, tens, temp, datetime], (err) => {
                    if (err) {
                        console.error('Erreur d\'insertion des mesures:', err);
                    } else {
                        console.log(`Données insérées pour id_point ${id}`);
                    }
                });

                // Vérification des seuils
                handleThresholds(id, tens, temp);
            });
        }
    } catch (error) {
        console.error('Erreur lors de l\'insertion des données XML:', error);
    }
}

// Gérer les seuils et générer une alerte
function handleThresholds(id_point, tens, temp) {
    const seuil_temp_max = 10;
    const seuil_tens_min = 30;
    const seuil_tens_max = 35;

    if (temp > seuil_temp_max) {
        createAlert(`Température trop élevée (${temp}°C) pour le point ${id_point}`);
    }
    if (tens < seuil_tens_min || tens > seuil_tens_max) {
        createAlert(`Tension hors limites (${tens}V) pour le point ${id_point}`);
    }
}

// Créer une alerte
function createAlert(motif) {
    const query = 'INSERT INTO alertes (motif, status) VALUES (?, "open")';
    db.execute(query, [motif], (err) => {
        if (err) {
            console.error('Erreur lors de la création de l\'alerte:', err);
        } else {
            console.log('Alerte créée:', motif);
        }
    });
}

// Appeler périodiquement la fonction d’insertion
setInterval(insertDataFromXML, 60000);
//setInterval(dropalert,60000000)
setInterval(dropalert,10000)
// === Lancer le serveur ===
app.listen(port, () => {
    console.log(`Serveur démarré sur http://localhost:${port}`);
});
