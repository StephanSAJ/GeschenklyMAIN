const cron = require('node-cron');
const { exec } = require('child_process');
require('dotenv').config();

function init() {
  // Stündliche Aggregation für tägliche Klicks
  cron.schedule('15 * * * *', () => {
    console.log('Running daily clicks aggregation job');
    exec('node scripts/aggregateDailyClicks.js', (error, stdout, stderr) => {
      if (error) {
        console.error(`Error executing daily script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`Error output daily script: ${stderr}`);
        return;
      }
      console.log(`Daily script output: ${stdout}`);
    });
  });

  // Alle 15 Minuten Aggregation für wöchentliche Klicks
  cron.schedule('*/15 * * * *', () => {
    console.log('Running weekly clicks aggregation job');
    exec('node scripts/aggregateWeeklyClicks.js', (error, stdout, stderr) => {
      if (error) {
        console.error(`Error executing weekly script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`Error output weekly script: ${stderr}`);
        return;
      }
      console.log(`Weekly script output: ${stdout}`);
    });
  });

  // Alle 15 Minuten Aggregation für monatliche Klicks
  cron.schedule('*/15 * * * *', () => {
    console.log('Running monthly clicks aggregation job');
    exec('node scripts/aggregateMonthlyClicks.js', (error, stdout, stderr) => {
      if (error) {
        console.error(`Error executing monthly script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`Error output monthly script: ${stderr}`);
        return;
      }
      console.log(`Monthly script output: ${stdout}`);
    });
  });

  // immer im 15 nach, jede Stunde
  cron.schedule('15 * * * *', () => {
    console.log('Running hourly clicks aggregation job');
    exec('node scripts/aggregateHourlyClicks.js', (error, stdout, stderr) => {
      if (error) {
        console.error(`Error executing hourly script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`Error output hourly script: ${stderr}`);
        return;
      }
      console.log(`Hourly script output: ${stdout}`);
    });
  });

  // Wöchentliche Kategoriebesuche berechnen - jede Stunde um 30
  cron.schedule('30 * * * *', () => {
    console.log('Running weekly category visits calculation job');
    exec('node scripts/calculateWeeklyCategoryVisits.js', (error, stdout, stderr) => {
      if (error) {
        console.error(`Error executing category visits script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`Error output category visits script: ${stderr}`);
        return;
      }
      console.log(`Category visits script output: ${stdout}`);
    });
  });

  // Wachsende Produkte berechnen - jede Stunde um 5
  cron.schedule('5 * * * *', () => { // This runs at 5 minutes past every hour
    console.log('Running rising products calculation job');
    exec('node scripts/risingProducts.js', (error, stdout, stderr) => {
      if (error) {
        console.error(`Error executing rising products script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`Error output rising products script: ${stderr}`);
        return;
      }
      console.log(`Rising products script output: ${stdout}`);
    });
  });
}

module.exports = { init };
