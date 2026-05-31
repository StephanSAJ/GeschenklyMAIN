require('dotenv').config();

const express = require('express');
const mongoose = require('mongoose');
const bodyParser = require('body-parser');
const cors = require('cors');
const fs = require('fs');
const https = require('https');
const scheduler = require('./scheduler'); // Scheduler importieren

const app = express();

app.use(cors());
app.use(bodyParser.json());

const ratingRoute = require('./routes/rating');
const analyticsRoute = require('./routes/analytics');
const feedbackRoute = require('./routes/feedback');
const likeRoute = require('./routes/like');
const weeklyTopRanksRoute = require('./routes/weeklyTopRanks'); // Neue Route importieren
const categoryVisitRoute = require('./routes/categoryVisit');
const trendingCategoriesRoute = require('./routes/trendingCategories');
const productFeedbackRoute = require('./routes/productFeedback');
const orderRoutes = require('./routes/order');
app.use('/api/order', orderRoutes);

app.use('/api/rating', ratingRoute);
app.use('/api', analyticsRoute);
app.use('/api/feedback', feedbackRoute);
app.use('/api/like', likeRoute);
app.use('/api/weekly-top-ranks', weeklyTopRanksRoute); // Neue Route verwenden

app.use('/api/category-visit', categoryVisitRoute);
app.use('/api/analytics', trendingCategoriesRoute);
app.use('/api/product-feedback', productFeedbackRoute);



const uri = process.env.DB_CONNECT;
console.log('DB_CONNECT:', uri);

mongoose.connect(uri, { useNewUrlParser: true, useUnifiedTopology: true })
  .then(() => {
    console.log('Connected to MongoDB');

    // Scheduler initialisieren, nachdem die Datenbankverbindung hergestellt wurde
    scheduler.init();

    if (process.env.NODE_ENV === 'production') {
      const privateKey = fs.readFileSync('/etc/letsencrypt/live/schindler-ventures.de/privkey.pem', 'utf8');
      const certificate = fs.readFileSync('/etc/letsencrypt/live/schindler-ventures.de/fullchain.pem', 'utf8');
      const ca = fs.readFileSync('/etc/letsencrypt/live/schindler-ventures.de/chain.pem', 'utf8');
      const credentials = {
        key: privateKey,
        cert: certificate,
        ca: ca,
      };

      const httpsServer = https.createServer(credentials, app);

      httpsServer.listen(3002, () => {
        console.log('HTTPS server running on port 3002.');
      });
    } else {
      app.listen(3002, () => {
        console.log('HTTP server running on port 3002.');
      });
    }
  })
  .catch(err => {
    console.error('Error connecting to MongoDB', err);
  });
