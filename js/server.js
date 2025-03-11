const express = require('express');
const mysql = require ('mysql2');
const bodyparser = require ('body-parser');

const app = express();
const port =3000;

//database setup (mysql)

const db = mysql.createConnection({
    host:'localhost',
    user: 'root',
    password:'password',
    database: 'vehicle_breakdown_assistance'
});

db.connect((err) => {
    if(err)throw err;
    console.log('connected to MySQL Database');
});

app.use(bodyParser.json());
app.use(express.static('public'));// server fronted files

// Api endpoint to get all breakdown requests

app.post('/api/requests', (req, res) =>{
    const query ='SELECT * FROM breakdown_requests ';
    db.query(query, (err, results) => {
        if (err) throw err;
        res.json ({requests: results});
    });
});

//Api endpoint to update request status
app.post('/api/update-requests', (req, res) =>{
    const { id, status}= req.body;
    const query='UPDATE breakdown_requests SET status = ? WHERE id= ?';
    db.query (query, [status,id], (err, results) => {
        if (err) throw err;
        res.json({ message: 'Request update successfully!'});
    });
});

//start the server
app.listen(port, ()=> {
    console.log('server running on http://localhost:${port}');
});

// fetch driver profile data from backend 



