document.addEventListener('DOMContentLoaded', function() {
    // Shop-Button im Footer auf mobilen Geräten anzeigen
    var shopButton = document.getElementById('shopButton');
    var originalButtonContainer = document.querySelector('.shop-button-container');
    var footerButtonContainer = document.createElement('div');
    footerButtonContainer.className = 'footer-shop-button';
    footerButtonContainer.style.display = 'none';
    document.body.appendChild(footerButtonContainer);

    var footerButton = null;

    function updateButtonPosition() {
        var rect = originalButtonContainer.getBoundingClientRect();
        var isMobile = window.innerWidth <= 768;

        if (rect.bottom < 0 && isMobile) {
            if (!footerButton) {
                footerButton = shopButton.cloneNode(true);
                footerButtonContainer.appendChild(footerButton);
            }
            footerButtonContainer.style.display = 'block';
            shopButton.style.visibility = 'hidden';
        } else {
            footerButtonContainer.style.display = 'none';
            shopButton.style.visibility = 'visible';
        }
    }

    window.addEventListener('scroll', updateButtonPosition);
    window.addEventListener('resize', updateButtonPosition);

    // Initiale Prüfung
    updateButtonPosition();

    // Funktion zum Anzeigen von Details
    function showDetails(type) {
        switch(type) {
            case 'ideal':
                alert('Ideal für Menschen, die Tee und ästhetische Erlebnisse schätzen.');
                break;
            case 'eigenschaften':
                alert('Dieses Produkt ist handgepflückt und Bio.');
                break;
            case 'preis':
                alert('Vergleichen Sie die Preise bei verschiedenen Anbietern.');
                break;
            case 'besonderheit':
                alert('Schauen Sie sich unser Video an, um das visuelle Erlebnis zu sehen!');
                break;
        }
    }

    // Funktion zum Filtern nach Tag
    function filterByTag(tag) {
        alert('Zeige alle Geschenke mit dem Tag: ' + tag);
    }

    // Like-Funktion für Geschenke
    var liked = false;
    function likeGift() {
        liked = !liked;
        var likeButton = document.getElementById('likeButton');
        var likesValue = document.getElementById('likesValue');
        likeButton.innerHTML = liked ? '❤️' : '🤍';
        likesValue.textContent = liked ? parseInt(likesValue.textContent) + 1 : parseInt(likesValue.textContent) - 1;
        alert(liked ? 'Geschenk zur Wunschliste hinzugefügt!' : 'Geschenk von der Wunschliste entfernt.');
    }

    // Funktion zum Anzeigen des Trend-Charts
    function showTrendChart() {
        var ctx = document.getElementById('trendChart').getContext('2d');
        var trendChartContainer = document.getElementById('trendChartContainer');
        trendChartContainer.style.display = 'block';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni'],
                datasets: [{
                    label: 'Beliebtheit im Zeitverlauf',
                    data: [75, 80, 85, 90, 87, 89],
                    borderColor: '#4a90e2',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    }

    // Interesse anderer Nutzer laden
    var likeButtonElement = document.getElementById('likeButton');
    var productId = likeButtonElement ? likeButtonElement.getAttribute('data-product-id') : null;
    if (productId) {
        fetch('https://schindler-ventures.de:3002/api/analytics/hourly/' + productId)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var currentHour = new Date().getHours();
                var last6HoursData = data.filter(function(item) {
                    var itemHour = item.hour;
                    var hourDifference = (currentHour - itemHour + 24) % 24;
                    return hourDifference <= 6;
                });

                var totalClicks = 0;
                last6HoursData.forEach(function(item) {
                    totalClicks += item.clicks;
                });

                var interestPercentage;
                var description;

                if (last6HoursData.length > 0) {
                    if (totalClicks < 5) {
                        interestPercentage = 20;
                        description = "Momentan ruhiges Interesse an diesem Geschenk.";
                    } else if (totalClicks < 20) {
                        interestPercentage = 50;
                        description = "Dieses Geschenk weckt gerade moderates Interesse.";
                    } else {
                        interestPercentage = 80;
                        description = "Dieses Geschenk ist gerade sehr beliebt!";
                    }
                } else {
                    interestPercentage = 0;
                    description = "Aktuell keine Daten verfügbar.";
                }

                document.getElementById('interestLevel').style.width = interestPercentage + '%';
                document.getElementById('interestDescription').textContent = description;
            })
            .catch(function(error) {
                console.error('Fehler beim Laden der Interessensdaten:', error);
                document.getElementById('interestDescription').textContent = "Entschuldigung, wir konnten die aktuellen Daten nicht laden.";
            });
    }

    // Daten für das Kategorien-Diagramm laden
    fetch('https://schindler-ventures.de:3002/api/analytics/top-categories-tags')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var combinedData = data.topCategories.concat(data.topTags)
                .sort(function(a, b) { return b.totalClicks - a.totalClicks; })
                .slice(0, 5);

            var labels = combinedData.map(function(item) { return item._id.category_name || item._id.tag_name; });
            var clicks = combinedData.map(function(item) { return item.totalClicks; });

            var ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: clicks,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderColor: 'rgba(255, 255, 255, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) { return context.parsed.y + ' Klicks'; }
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                fontSize: 12,
                                fontStyle: 'bold'
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                fontSize: 12,
                                callback: function(value) { return value + ' Klicks'; }
                            },
                            gridLines: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }]
                    }
                }
            });
        })
        .catch(function(error) {
            console.error('Fehler beim Abrufen der Daten:', error);
        });

    // Funktion zum Absenden von Feedback
    function submitFeedback() {
        var feedback = document.getElementById('feedback').value;
        if (feedback.trim() === '') {
            alert('Bitte geben Sie Ihr Feedback ein.');
        } else {
            alert('Vielen Dank für Ihr Feedback: ' + feedback);
            document.getElementById('feedback').value = '';
        }
    }

    // Event-Listener für Feedback-Button
    var feedbackButton = document.querySelector('.feedback-submit');
    if (feedbackButton) {
        feedbackButton.addEventListener('click', submitFeedback);
    }

    // Event-Listener für Like-Button
    if (likeButtonElement) {
        likeButtonElement.addEventListener('click', likeGift);
    }
});
