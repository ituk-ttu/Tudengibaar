document.getElementById('crashPriceForm').addEventListener('submit', async (e) => {
    e.preventDefault(); // Prevent normal form submission

    const formData = new FormData(e.target);

    try {
        const response = await fetch('apply_crash_price.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        const responseDiv = document.getElementById('response');
        if (result.success) {
            responseDiv.innerHTML = `<p style="color:green;">${result.message}</p>`;
        } else {
            responseDiv.innerHTML = `<p style="color:red;">${result.error}</p>`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
});


document.getElementById('resetPrices').addEventListener('submit', async (e) => {
    e.preventDefault()

    const formData = new FormData(e.target);

    try {
        const response = await fetch('reset_prices.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        const responseDiv = document.getElementById('response');
        if (result.success) {
            responseDiv.innerHTML = `<p style="color:green;">${result.message}</p>`;
        } else {
            responseDiv.innerHTML = `<p style="color:red;">${result.error}</p>`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
})

document.getElementById('soldOut').addEventListener('submit', async (e) => {
    e.preventDefault()

    const formData = new FormData(e.target);

    try {
        const response = await fetch('apply_sold_out.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        const responseDiv = document.getElementById('response');
        if (result.success) {
            responseDiv.innerHTML = `<p style="color:green;">${result.message}</p>`;
        } else {
            responseDiv.innerHTML = `<p style="color:red;">${result.error}</p>`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
})