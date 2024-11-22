const express = require('express');
const mysql = require('mysql2');
const WebSocket = require('ws');
const axios = require('axios');
const xml2js = require('xml2js'); // Pour parser le fichier XML

// Configuration du serveur Express
const app = express();
const port = 3000;
app.use(express.json());

// Configuration de la base de données MySQL
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',  // Remplacez par votre mot de passe MySQL
    database: 'ubmap'   // Remplacez par le nom de votre base de données
});

// Connexion WebSocket pour les alertes en temps réel
const wss = new WebSocket.Server({ port: 8080 });

wss.on('connection', ws => {
    console.log('Client connecté');
    ws.on('message', message => {
        console.log('Message reçu: ', message);
    });

    // Fonction pour envoyer une alerte au frontend via WebSocket
    function sendAlertToFrontend(message) {
        ws.send(JSON.stringify({ alert: message }));
    }

    // Fonction pour insérer les données et vérifier les seuils
    async function insertData(req, res) {
        console.log('Données reçues:', req.body);
        const { id_point, tens, temp, datetime } = req.body;

        // Seuils de contrôle pour la température et la tension
        const seuil_temp_max = 10;  // Température maximale
        const seuil_tens_min = 30;  // Tension minimale
        const seuil_tens_max = 35;  // Tension maximale

        // Vérification des seuils
        let alertMessage = '';
        if (temp > seuil_temp_max) {
            alertMessage = 'Température trop élevée !';
        }
        if (tens < seuil_tens_min || tens > seuil_tens_max) {
            alertMessage = 'Tension hors des limites !';
        }

        // Vérification si les données existent déjà dans la base
        const queryCheck = 'SELECT COUNT(*) AS count FROM mesures WHERE id_point = ? AND heure = ?';
        db.execute(queryCheck, [id_point, datetime], (err, result) => {
            if (err) {
                console.error('Erreur de vérification:', err);
                res.status(500).send('Erreur de vérification');
                return;
            }

            if (result[0].count > 0) {
                console.log('Données déjà présentes pour id_point', id_point);
                res.status(200).send('Données déjà présentes');
            } else {
                // Insertion dans la base de données si pas déjà présentes
                const query = 'INSERT INTO mesures (id_point, tens, temp, heure) VALUES (?, ?, ?, ?)';
                db.execute(query, [id_point, tens, temp, datetime], (err, result) => {
                    if (err) {
                        console.error('Erreur d\'insertion:', err);
                        res.status(500).send('Erreur d\'insertion');
                        return;
                    }
                    console.log(`Données insérées pour id_point ${id_point}`);

                    // Envoi de l'alerte si un seuil est franchi
                    if (alertMessage) {
                        sendAlertToFrontend(alertMessage);
                    }

                    res.status(200).send('Données insérées avec succès');
                });
            }
        });
    }

    // Route pour insérer des données via POST
    app.post('/insertData', insertData);

    // Fonction pour récupérer les points depuis la table `point` et leur IP
    function getPoints() {
        return new Promise((resolve, reject) => {
            const query = 'SELECT id, ip_address FROM points';  // La table "point" contient les "id_point" et "IP"
            db.execute(query, (err, results) => {
                if (err) {
                    console.error('Erreur lors de la récupération des points:', err);
                    reject(err);
                } else {
                    console.log('Points récupérés:', results);
                    resolve(results);
                }
            });
        });
    }

    // Fonction pour récupérer le XML d'un automate via son IP
    async function fetchXMLFromIP(ip) {
        console.log(`Tentative de récupération du XML pour l'IP: ${ip}`);
        try {
            const response = await axios.get(`http://${ip}:25001/status.xml?a=admin:Zx23-Zx81`);
            console.log(`XML récupéré depuis ${ip}`);
            return response.data;
        } catch (error) {
            console.error(`Erreur de récupération du fichier XML depuis ${ip}:`, error);
            sendAlertToFrontend(`Automate ${ip} déconnecté ou inaccessible !`);
            return null;
        }
    }

    // Fonction pour insérer les données depuis un fichier XML
    async function insertDataFromXML() {
        console.log('Début du processus d\'insertion depuis XML');
        try {
            console.log('Récupération des points...');
            const points = await getPoints();
            console.log('Points récupérés:', points);

            for (const point of points) {
                const { id, ip_address } = point;

                // Récupérer le fichier XML depuis l'IP de l'automate
                const xmlData = await fetchXMLFromIP(ip_address);
                console.log(`XML récupéré pour id_point ${id}: ${xmlData ? 'Oui' : 'Non'}`);

                if (!xmlData) {
                    console.log(`Aucun XML récupéré pour id_point ${id}`);
                    continue;
                }

                xml2js.parseString(xmlData, (err, result) => {
                    if (err) {
                        console.error('Erreur lors du parsing du XML:', err);
                        return;
                    }

                    console.log('XML parsé:', result);

                    // Accéder aux éléments nécessaires du XML
                    const temperature1 = result.Monitor.Temperature1 ? result.Monitor.Temperature1[0] : null;
                    const analogInput1 = result.Monitor.AnalogInput1 ? result.Monitor.AnalogInput1[0] : null;
                    const id = result.Monitor.ID ? result.Monitor.ID[0] : null;

                    // Vérification si les valeurs existent dans le XML
                    if (!temperature1 || !analogInput1 || !id) {
                        console.error('Des données nécessaires manquent dans le XML');
                        return;
                    }

                    // Extraction des valeurs et nettoyage des unités
                    const temp = parseFloat(temperature1.replace('°C', '').trim());
                    const tens = parseFloat(analogInput1.replace('V', '').trim());
                    const datetime = new Date().toISOString().slice(0, 19).replace('T', ' ');

                    // Log pour vérifier les données extraites
                    console.log(`Temp: ${temp}, Tension: ${tens}, Date/Heure: ${datetime}`);

                    // Appeler la fonction insertData pour insérer les données et envoyer l'alerte
                    const req = { body: { id_point: id, tens, temp, datetime } };
                    insertData(req, { status: () => ({ send: console.log }) });
                });
            }
        } catch (error) {
            console.error('Erreur lors de l\'extraction des données XML:', error);
        }
    }

    // Appeler la fonction pour insérer les données depuis le fichier XML toutes les minutes
    setInterval(insertDataFromXML, 60 * 1000);  // Exécute toutes les 1 minute
});

// Démarrer le serveur
app.listen(port, () => {
    console.log(`Serveur backend démarré sur http://localhost:${port}`);
});
