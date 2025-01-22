document.addEventListener("DOMContentLoaded", function () {
    let activeIndex = 0; // Initially set the first drink to be active
    let currentIndex = 0;  // Current index for the chart cycling
    let data = []; // To store fetched data
    let seriesData = []; // To store price history data
    let chart = null;  // Declare chart variable globally
    let isDataLoaded = false;  // Flag to ensure data is loaded before cycling

    function fetchAndUpdateData() {
        fetch('../get_data.php')
            .then(response => response.json())
            .then(newData => {
                data = newData;

                const cocktailTableBody = document.getElementById('cocktail-list').querySelector('tbody');
                const shotTableBody = document.getElementById('shot-list').querySelector('tbody');
                const beverageTableBody = document.getElementById('beverage-list').querySelector('tbody');

                // Clear current table contents
                cocktailTableBody.innerHTML = '';
                shotTableBody.innerHTML = '';
                beverageTableBody.innerHTML = '';

                // Populate tables based on drink type
                data.forEach((drink, index) => {
                    const row = document.createElement('tr');
                    row.dataset.index = index; // Add data-index attribute

                    // Add a class for shots or cocktails
                    if (drink.is_cocktail) {
                        row.classList.add('drink-item', 'cocktail-item');

                    } else if (drink.is_shot) {
                        row.classList.add('drink-item', 'shot-item');
                    } else {
                        row.classList.add('drink-item', 'beverage-item');
                    }

                    const currentPrice = parseFloat(drink.current_price);
                    const difference = drink.difference !== undefined ? parseFloat(drink.difference) : null;

                    const nameCell = document.createElement('td');
                    nameCell.classList.add('name-cell');

                    const nameElement = document.createElement('div');
                    nameElement.classList.add('drink-name');
                    nameElement.textContent = drink.name;

                    nameCell.appendChild(nameElement);

                    const priceCell = document.createElement('td');
                    priceCell.classList.add('price_cell');
                    priceCell.textContent = !isNaN(currentPrice) ? currentPrice.toFixed(2) + ' €' : 'N/A';



                    const differenceCell = document.createElement('td');
                    if (difference !== null) {
                        differenceCell.textContent = difference.toFixed(2) + ' €';
                        differenceCell.classList.add('price-change');
                        if (difference < 0) {
                            differenceCell.classList.add('red');
                            differenceCell.innerHTML += ' <i class="fa-solid fa-arrow-up fa-flip-vertical"></i>';
                        } else if (difference > 0) {
                            differenceCell.classList.add('green');
                            differenceCell.innerHTML += ' <i class="fa-solid fa-arrow-up"></i>';
                        }
                    } else {
                        differenceCell.textContent = 'N/A';
                    }

                    // Highlight the active row
                    if (index === activeIndex) {
                        row.classList.add('active');
                    }


                    if (drink.is_soldout) {
                        row.classList.add('soldout');
                        differenceCell.innerHTML = 'Sold Out';
                        differenceCell.classList.remove('green', 'red');
                        priceCell.innerHTML = '';
                    }


                    // Append cells to the row
                    row.appendChild(nameCell);
                    row.appendChild(priceCell);
                    row.appendChild(differenceCell);

                    // Append the row to the appropriate table based on type
                    if (drink.is_cocktail) {
                        cocktailTableBody.appendChild(row);
                    } else if (drink.is_shot) {
                        shotTableBody.appendChild(row);
                    } else {
                        beverageTableBody.appendChild(row);
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }

    function fetchPriceHistory() {
        fetch('../get_price_history.php')
            .then(response => response.json())
            .then(priceHistoryData => {
                // Process price history data to prepare seriesData
                seriesData = priceHistoryData.map(drink => {
                    const priceHistory = drink.price_history.map(entry => {
                        // Parse timestamp as a full date (adjust depending on how timestamps are stored in your DB)
                        const timestamp = new Date(entry.timestamp).getTime();  // Converts to milliseconds
                        return [
                            timestamp,
                            parseFloat(entry.price)  // Format price to two decimals
                        ];
                    }).sort((a, b) => a[0] - b[0]);  // Sort by timestamp (ascending)

                    return {
                        id: drink.id,  // Drink ID
                        name: drink.name,  // Drink name
                        data: priceHistory  // Sorted price history data
                    };
                });

                // Sort seriesData by id to ensure the correct order
                seriesData.sort((a, b) => a.id - b.id);  // Ensure it's sorted by id

                // Initialize the chart once data is populated, but only if it's the first load
                if (seriesData.length > 0 && !isDataLoaded) {
                    initializeChart();  // Initialize the chart with the first drink
                    isDataLoaded = true; // Set flag to true once data is loaded
                    setInterval(cycleDrinks, 5000);  // Start cycling drinks after data is ready
                } else {
                    console.error("No data available for chart.");
                }
            })
            .catch(error => {
                console.error('Error fetching price history data:', error);
            });
    }


    // Function to initialize the chart once
    function initializeChart() {
        const data = seriesData[currentIndex];
        if (!data) {
            console.log("No data available for this drink.");
            return;
        }
        chart = Highcharts.chart('chart-container', {
            chart: {
                type: 'area',
                backgroundColor: '#1F1836'
            },
            title: {
                text: data.name + ' Prices with Prognosis',
                style: {
                    color: '#ffffff',
                    fontSize: '15px'
                }
            },
            legend: {
                itemStyle: {
                    color: '#FFFFFF',
                    fontSize: '15px'
                },
            },
            xAxis: {
                type: 'datetime',
                dateTimeLabelFormats: {
                    day: '%H:%M',
                },
                labels: {
                    style: {
                        color: '#FFFFFF',
                        fontSize: '15px'
                    }
                }
            },
            yAxis: {
                min: 2,
                title: {
                    text: 'Price (EUR)',  // Y-axis title text
                    style: {
                        color: '#FFFFFF',  // Title color
                        fontSize: '15px',    // Title font size
                        margin: '15px'
                    }
                },
                labels: {
                    style: {
                        color: '#FFFFFF',  // Y-axis label color
                        fontSize: '15px',   // Y-axis label font size
                        fontWeight: 'bold'  // Y-axis label font weight (optional)
                    },

                },
                gridLineColor: '#352F4A'  // Color of the grid lines
            },
            series: [{
                name: data.name,
                data: data.data,  // Only show actual price data initially
                color: "#6A74B4",  // Explicit color for actual prices
                fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, "rgba(106, 116, 180, 0.5)"],  // Use rgba directly for transparency
                        [1, "rgba(106, 116, 180, 0)"]
                    ]
                },
            }, {
                name: data.name + ' Prognosis',  // Name for forecast data
                data: generateRandomForecast(data.data),  // Start with forecast data
                color: "#ff5733",  // Different color for forecast
                dashStyle: 'ShortDash',  // Dashed line for forecast
                fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, "rgba(255, 87, 51, 0.5)"],  // The color #ff5733 with 50% opacity
                        [1, "rgba(255, 87, 51, 0)"]      // The color #ff5733 with 0% opacity (fully transparent)
                    ]
                },
                zIndex: 1,  // Ensure forecast is above the actual price data
            }],
            tooltip: {
                xDateFormat: '%HH:%mm',
                backgroundColor: '#352F4A',
                style: {
                    color: '#FFFFFF'
                }
            }
        });
    }

    function generateRandomForecast(data) {
        const forecastData = [];
        const randomFactor = 0.05;  // 5% random fluctuation factor
        const lastTimestamp = data[data.length - 1][0];  // Get the last timestamp
        const lastPrice = data[data.length - 1][1];  // Get the last price

        // Add the latest price as the first forecast point
        forecastData.push([lastTimestamp, parseFloat(lastPrice.toFixed(2))]);

        // Generate random forecast data points for the next 5 intervals (30 minutes in total)
        for (let i = 1; i <= 5; i++) {
            const nextTimestamp = lastTimestamp + i * 6 * 60 * 1000;  // 6 minutes interval
            const randomAdjustment = (Math.random() - 1) * randomFactor;  // Random adjustment within -5% to 5%
            const newPrice = lastPrice + (lastPrice * randomAdjustment);  // Apply the random adjustment to the price
            forecastData.push([nextTimestamp, parseFloat(newPrice.toFixed(2))]);  // Push the new forecast point
        }

        return forecastData;
    }

    // Function to update the chart with new data
    function updateChart(index) {
        const data = seriesData[index];
        if (!data) {
            console.error("No data available for index", index);
            return;
        }

        if (chart && chart.series && chart.series[0]) {
            chart.setTitle({
                text: data.name + ' Prices with Prognosis'
            });

            // Update the chart with actual prices
            chart.series[0].update({
                name: data.name,
                data: data.data,
            });

            // Generate and update prognosis data
            const forecastData = generateRandomForecast(data.data);
            chart.series[1].update({
                name: data.name + ' Prognosis',
                data: forecastData,
            });
        }
    }

    // Function to cycle through drinks
    function cycleDrinks() {
        if (!isDataLoaded) {
            console.log("Data is not loaded yet, skipping cycle.");
            return;
        }

        // Increment currentIndex and wrap around if necessary
        currentIndex = (currentIndex + 1) % seriesData.length;
        activeIndex = currentIndex; // Sync active index with currentIndex

        // Update the table and chart accordingly
        updateTable();
        updateChart(currentIndex);
    }

    // Function to update the active row in the table
    function updateTable() {
        const rows = document.querySelectorAll('.drink-item');
        rows.forEach(row => row.classList.remove('active'));

        const activeRow = rows[activeIndex];
        if (activeRow) {
            activeRow.classList.add('active');
        }
    }

    fetchPriceHistory();
    setInterval(fetchAndUpdateData, 2000);
    setInterval(fetchPriceHistory, 75000);
});