<script>
    /* Author: Mohamed Alalwan 201601446*/

    //setting products
    let products = [
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $item) : ?> {
                    id: '<?= $item['item_id'] ?>',
                    name: '<?= $item['item_title'] ?>',
                    price: <?= $item['item_price'] ?>,
                    menu: '<?= $item['item_type'] ?>',
                    image: '<?= $item['item_image'] ?>',
                    inCart: 0
                },
            <?php endforeach; ?>
        <?php endif; ?>
    ]

    //setting menu names
    let menus = [
        <?php if (!empty($menus)) : ?>
            <?php foreach ($menus as $menu) : ?> "<?= $menu ?>",
            <?php endforeach; ?>
        <?php endif; ?>
    ];

    //set local storage
    <?php if (!empty($products)) : ?>
        <?php
        $cartNum = $totalPrice = 0;
        $productsInCart = json_encode($products);
        foreach ($products as $product) {
            //get item in cart number
            $cartNum += $product['inCart'];
            $totalPrice += $product['price'] * $product['inCart'];
        }
        ?>
        localStorage.setItem('cartNum', '<?= $cartNum ?>');
        localStorage.setItem('productsInCart', '<?= $productsInCart ?>');
        localStorage.setItem('totalPrice', '<?= $totalPrice ?>');
    <?php endif; ?>

    //display items in menu
    function displayItemsOnMenu(menu) {
        let services = document.getElementById("services");
        let servicesContainer = document.getElementsByClassName("all-services-container")[0];
        let cart = document.querySelector("#cart");
        let serviceDuration = document.querySelector(".service-duration");

        //if menu exists
        if (menus.indexOf(menu) !== -1) {
            //display service duration
            serviceDuration.style.display = "block";

            //filtering products based on menu
            let filtered = products.filter(function(item) {
                return item.menu == menu;
            })
            //generate container if doesnt exist
            if (!servicesContainer) {
                servicesContainer = document.createElement("div");
                servicesContainer.className = "all-services-container";
                services.insertBefore(servicesContainer, services.firstChild);
            }
            //add menu to html
            servicesContainer.innerHTML = '';
            filtered.forEach(
                function(item) {
                    servicesContainer.innerHTML += `
                        <div class="service-container">
                            <div class="service-product">
                            <img class="service-image" src="${item.image}" alt="omelette">
                            <div class="service-overlay">
                                <div class="buttons">
                                    <h3>${item.price.toFixed(3)} BHD</h3>
                                    <button class="add-button" type="button" onclick="addItem(${item.id})">
                                    <ion-icon name="add-circle"></ion-icon> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="service-text">
                            <h3>${item.name}</h3>
                        </div>
                    `;
                }
            )
            if (!cart) {
                services.innerHTML += `
                <h3>Total Items <span id="cart">0</span></h3>
                <div class="product titles container">
                    <div class="row">
                        <h4 class="removeIcon"></h4>
                        <h4 class="product">PRODUCT</h4>
                        <h4 class="price">PRICE</h4>
                        <h4 class="quantity">QUANTITY</h4>
                        <h4 class="total">TOTAL</h4>
                    </div>
                </div>
                <div id="item" class="product container">
                    <div class="row">
                        <h2>No items!</h2>
                    </div>
                </div>
                `;
            }
            displayCartItems();
            onLoadCartNum();
            totalChargeDuration();

        } //doesn't exist 
        else {
            //hide service duration
            serviceDuration.style.display = "none";
            //remove all services
            services.innerHTML = '';
        }
    }
    let menuSelection = document.getElementById('menus');
    displayItemsOnMenu(menuSelection.value);

    //adding items to cart
    function addItem(id) {
        let index = products.findIndex(item => item.id == id);
        //update cart num
        cartNum(products[index]);
        //update total price
        calculateTotal(products[index]);
        //show cart content
        displayCartItems();
    }

    //updating cart span according to storage
    function onLoadCartNum() {
        let productNum = localStorage.getItem('cartNum');
        let cart = document.querySelector("#cart");
        if (productNum && cart) {
            cart.textContent = productNum;
        }
    }
    onLoadCartNum();

    //loading all charge with duration
    function totalChargeDuration() {
        let duration = document.querySelector("#duration").value;
        let durationSpan = document.querySelector("#durationSpan");
        let totalSpan = document.querySelector("#total2");
        let totalDurationSpan = document.querySelector("#totalWithDuration");
        let totalPrice =
            parseFloat(localStorage.getItem('totalPrice'));
        if (totalSpan && totalDurationSpan && totalPrice) {
            totalSpan.textContent = totalPrice.toFixed(3) + " BHD";
            durationSpan.textContent = duration;
            totalDurationSpan.textContent = "= " + (totalPrice * duration).toFixed(3) + " BHD for " + duration + " day/s";
        }
    }
    totalChargeDuration();

    //display all cart items and their info
    function displayCartItems() {
        let cartItems = localStorage.getItem('productsInCart');
        cartItems = JSON.parse(cartItems);
        let productContainer = document.querySelector('.product.container:not(.titles)');
        let container = document.querySelector('#services');
        let totalPrice = localStorage.getItem('totalPrice');
        //delete if already displayed
        if (document.getElementById("total")) {
            let productContainers = document.querySelectorAll('.product.container:not(.titles)');
            productContainers.forEach(con => con.remove());
        }
        if (cartItems && productContainer) {
            productContainer.innerHTML = '';
            Object.values(cartItems).map(item => {
                container.innerHTML += `
                <div id=${item.id} class="product container">
                    <div class="row">
                        <h4 class="removeIcon">
                            <a href="#!" class="trash" onclick="removeItem(${item.id})"
                            ><ion-icon name="trash"></ion-icon></a>
                        </h4>
                        <h4 class="product">${item.name}</h4>
                        <h4 class="price">${item.price} BHD</h4>
                        <h4 class="quantity">
                            <a href="#!" onclick="decreaseItemQuantity(${item.id})" class="decrease"
                            ><ion-icon name="remove-circle"></ion-icon></a> <span id="${item.id}-quantity">${item.inCart}</span> <a href="#!" onclick="increaseItemQuantity(${item.id})" class="increase"
                            ><ion-icon name="add-circle"></ion-icon></a>
                        </h4>
                        <h4 class="total"><span id="${item.id}-total">${(item.price * item.inCart).toFixed(3)}</span> BHD</h4>
                    </div>
                </div>
                `
            });
            container.innerHTML += `
            <div class="product container">
            <div class="row">
                <h2>Total Price: <span id="total">${totalPrice} BHD</span></h2>
            </div>
            </div>
        
            `;
        }
    }
    displayCartItems();

    //saving cart num
    function cartNum(product) {
        let productNum = localStorage.getItem('cartNum');
        productNum = parseInt(productNum);
        let cart = document.querySelector("#cart");
        if (productNum) {
            productNum += 1;
            localStorage.setItem('cartNum', productNum);
            cart.textContent = productNum;
        } else {
            productNum = 1;
            localStorage.setItem('cartNum', productNum);
            cart.textContent = productNum;
        }
        //save item
        setItems(product);
    }

    //saving products
    function setItems(product) {
        let cartItems = localStorage.getItem('productsInCart');
        cartItems = JSON.parse(cartItems);
        if (cartItems != null) {
            if (cartItems[product.id] == undefined) {
                cartItems = {
                    ...cartItems,
                    [product.id]: product
                }
            }
            cartItems[product.id].inCart += 1;
        } else {
            product.inCart = 1;

            cartItems = {
                [product.id]: product
            }
        }
        localStorage.setItem("productsInCart", JSON.stringify(cartItems));
    }

    //calculating total price
    function calculateTotal(product) {
        let totalPrice =
            parseFloat(localStorage.getItem('totalPrice'));
        if (totalPrice) {
            totalPrice += product.price;
            localStorage.setItem("totalPrice", totalPrice.toFixed(3));
        } else {
            totalPrice = product.price;
            localStorage.setItem("totalPrice", totalPrice.toFixed(3));
        }
        totalChargeDuration();
    }

    //removing items
    function removeItem(id) {
        //getting values
        let cartItems = localStorage.getItem('productsInCart');
        cartItems = JSON.parse(cartItems);
        let totalPrice =
            parseFloat(localStorage.getItem('totalPrice'));
        let productNum = localStorage.getItem('cartNum');
        productNum = parseInt(productNum);

        //subtract total
        totalPrice -= cartItems[id].price * cartItems[id].inCart;
        localStorage.setItem("totalPrice", totalPrice.toFixed(3));

        //updating cart num
        productNum -= cartItems[id].inCart;
        localStorage.setItem('cartNum', productNum);
        cart.textContent = productNum;

        //delete item
        let index = products.findIndex(item => item.id == id);
        products[index].inCart = 0;
        delete cartItems[id];
        localStorage.setItem("productsInCart", JSON.stringify(cartItems));

        /*----------------------------html---------------------------*/
        //remove item html element
        document.getElementById(id).remove();
        //update total span
        document.getElementById("total").textContent = totalPrice.toFixed(3) + " BHD";

        totalChargeDuration();
    }

    //increasing item quantity
    function increaseItemQuantity(id) {
        //getting values
        let cartItems = localStorage.getItem('productsInCart');
        cartItems = JSON.parse(cartItems);
        let totalPrice =
            parseFloat(localStorage.getItem('totalPrice'));
        let productNum = localStorage.getItem('cartNum');
        productNum = parseInt(productNum);

        //increase total
        totalPrice += cartItems[id].price;
        localStorage.setItem("totalPrice", totalPrice.toFixed(3));

        //updating cart num
        productNum += 1;
        localStorage.setItem('cartNum', productNum);

        //update item quantity
        cartItems[id].inCart += 1;
        localStorage.setItem("productsInCart", JSON.stringify(cartItems));

        /*----------------------------html---------------------------*/
        //update cart num span
        let cart = document.querySelector("#cart");
        cart.textContent = productNum;
        //update total span
        document.getElementById("total").textContent = totalPrice.toFixed(3) + " BHD";
        //update item quantity span
        document.getElementById(id + "-quantity").textContent = cartItems[id].inCart;
        //update item total span
        document.getElementById(id + "-total").textContent = (cartItems[id].inCart * cartItems[id].price).toFixed(3);

        totalChargeDuration();
    }

    //decrease item quantity
    function decreaseItemQuantity(id) {
        //getting values
        let cartItems = localStorage.getItem('productsInCart');
        cartItems = JSON.parse(cartItems);
        let totalPrice =
            parseFloat(localStorage.getItem('totalPrice'));
        let productNum = localStorage.getItem('cartNum');
        productNum = parseInt(productNum);

        if (cartItems[id].inCart == 1) {
            removeItem(id);
            return;
        }
        //decrease total
        totalPrice -= cartItems[id].price;
        localStorage.setItem("totalPrice", totalPrice.toFixed(3));

        //updating cart num
        productNum -= 1;
        localStorage.setItem('cartNum', productNum);

        //update item quantity
        cartItems[id].inCart -= 1;
        localStorage.setItem("productsInCart", JSON.stringify(cartItems));

        /*----------------------------html---------------------------*/
        //update cart num span
        let cart = document.querySelector("#cart");
        cart.textContent = productNum;
        //update total span
        document.getElementById("total").textContent = totalPrice.toFixed(3) + " BHD";
        //update item quantity span
        document.getElementById(id + "-quantity").textContent = cartItems[id].inCart;
        //update item total span
        document.getElementById(id + "-total").textContent = (cartItems[id].inCart * cartItems[id].price).toFixed(3);

        totalChargeDuration();
    }
</script>