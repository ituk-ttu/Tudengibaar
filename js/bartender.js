const cart = [];
let totalPrice = 0;

function addToCart(id, name, price) {
    const cartItem = cart.find(item => item.id === id);
    if (cartItem) {
        cartItem.quantity += 1;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    totalPrice += price;
    updateCartDisplay();
    flashTotalPrice();
}

function removeFromCart(id) {
    const cartItemIndex = cart.findIndex(item => item.id === id);
    if (cartItemIndex !== -1) {
        const cartItem = cart[cartItemIndex];
        if (cartItem.quantity > 1) {
            cartItem.quantity -= 1;
        } else {
            cart.splice(cartItemIndex, 1);
        }
        totalPrice -= cartItem.price;
        updateCartDisplay();
        flashTotalPrice();
    }
}

function updateCartDisplay() {
    const cartItemsElement = document.getElementById('cart-items');
    const totalPriceElement = document.getElementById('total-price');
    const totalPriceElementUp = document.getElementById('total-price-up');
    cartItemsElement.innerHTML = '';

    cart.forEach(item => {
        const listItem = document.createElement('li');
        const removeButton = document.createElement('button');
        removeButton.classList.add('remove-button');
        removeButton.onclick = () => removeFromCart(item.id);
        listItem.textContent = `${item.name} x${item.quantity} - €${(item.price * item.quantity).toFixed(2)}`;
        listItem.appendChild(removeButton);
        cartItemsElement.appendChild(listItem);
    });

    totalPriceElement.textContent = `Total: €${totalPrice.toFixed(2)}`;
    totalPriceElementUp.textContent = `Total: €${totalPrice.toFixed(2)}`;
}

function flashTotalPrice() {
    const totalPriceElement = document.getElementById('total-price');
    totalPriceElement.style.transition = 'transform 0.2s ease';
    totalPriceElement.style.transform = 'scale(1.1';
    setTimeout(() => {
        totalPriceElement.style.transform = 'scale(1)';
    }, 200);
}

function confirmPurchase() {
    if (cart.length === 0) {
        alert('Your cart is empty. Please add drinks to the cart before confirming the purchase.');
        return;
    }

    const drinkIds = cart.map(item => item.id);
    const quantities = cart.map(item => item.quantity);

    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ drink_ids: drinkIds, quantities: quantities })
    })
        .then(response => {
            if (response.ok) {
                alert('Purchase confirmed!');

                cart.length = 0;
                totalPrice = 0;

                updateCartDisplay();
            } else {
                alert('Error confirming purchase. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    function fetchAndUpdateData() {
        fetch('../get_data.php') // Adjust path to your data-fetching script
            .then(response => response.json())
            .then(drinks => {
                const drinkButtonsContainer = document.querySelector('.drink-buttons');
                drinkButtonsContainer.innerHTML = '';  // Clear existing buttons

                drinks.forEach(drink => {
                    // Create the button element
                    const button = document.createElement('button');
                    button.type = 'button';

                    // Set class based on price difference
                    if (drink.difference !== null) {
                        button.className = `drink-button ${
                            drink.difference > 0 ? 'green' : drink.difference < 0 ? 'red' : ''
                        }`;
                    } else {
                        button.className = 'drink-button';
                    }

                    // Set the onClick handler for adding to cart
                    button.setAttribute('onclick', `addToCart(${drink.id}, "${drink.name}", ${drink.current_price})`);

                    // Create the inner content for the button
                    const differenceText = drink.difference !== null
                        ? `€${drink.difference}`
                        : '';

                    button.innerHTML = `
                        <div class="drink-info">
                            <p class="drink-name">${drink.name}</p>
                            <p class="drink-price">€${drink.current_price}</p>
                        </div>
                        <p class="drink-difference">${differenceText}</p>
                    `;

                    if (drink.is_soldout) {
                        button.innerHTML = drink.name + '<br>Sold Out';
                        button.classList.remove('green', 'red');
                        button.type = 'button';
                    }

                    // Append the button to the container
                    drinkButtonsContainer.appendChild(button);
                });
            })
            .catch(error => {
                console.error('Error fetching drinks:', error);
            });
    }

    fetchAndUpdateData();
    setInterval(fetchAndUpdateData, 1500);
});
