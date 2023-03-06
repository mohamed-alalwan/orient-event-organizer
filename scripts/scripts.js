/* Author: Mohamed Alalwan 201601446*/

//progress bar
window.onload = function _() {
    const prevBtns = document.querySelectorAll(".prev");
    const nextBtns = document.querySelectorAll(".next");
    const progress = document.getElementById("progress");
    const formsSteps = document.querySelectorAll(".form-step");
    const progressSteps = document.querySelectorAll(".progress-step");
    let formStepsNum = 0;

    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if(validateForm(btn)){
                formStepsNum++;
                updateFormSteps();
                updateProgressBar();
            }
        })
    })

    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            formStepsNum--;
            updateFormSteps();
            updateProgressBar();
        })
    })

    function updateFormSteps() {
        formsSteps.forEach(formStep => {
            formStep.classList.contains("active") &&
                formStep.classList.remove("active");
        })
        formsSteps[formStepsNum].classList.add("active");
    }

    function updateProgressBar() {
        progressSteps.forEach((progressStep, i) => {
            if (i < formStepsNum + 1) {
                progressStep.classList.add('active')
            } else {
                progressStep.classList.remove('active')
            }
        })
        updateBarWidth();
    }

    function updateBarWidth() {
        var activeProgressSteps = document.querySelectorAll(".progress-step.active");

        if (activeProgressSteps.length == 2)
            progress.style.width = "33.33%";
        else if (activeProgressSteps.length == 3)
            progress.style.width = "66.66%";
        else if (activeProgressSteps.length == 4)
            progress.style.width = "100%";
        else
            progress.style.width = "0%";
    }
}

//validate form
function validateForm(btn){
    if(btn.id == "event_button"){
        let name = document.getElementById("event_name");
        let desc = document.getElementById("description");
        let error = document.querySelector(`.error.${btn.id}`);
        if(name.value == ""){
            error.innerHTML = "⚠ Event name can't be empty!";
            return false;
        }
        if(desc.value == "" || desc.value.length < desc.minLength){
            error.innerHTML = "⚠ Event description must atleast contain 10 character and cannot pass 250 characters!";
            return false
        }
        error.innerHTML = "";
        return true;
    }else if(btn.id == "service_button"){
        //saving products selected to input
        saveProducts();
        return true;
    }       
    else{
        return true;
    }
}

//saving product items to json
function saveProducts(){
    let menuSelect = document.getElementById('menus');
    let jsonProducts = document.getElementById("json_products");
    if(menus.indexOf(menuSelect.value) != -1){
        let cartItems = JSON.parse(localStorage.getItem('productsInCart'));
        if(cartItems){
            for (const key of Object.keys(cartItems)) {
                delete cartItems[key].image;
            }
            jsonProducts.value = JSON.stringify(cartItems);
        }else{
            jsonProducts.value = '';
        }
    }else{
        jsonProducts.value = '';
    }
}

//display content
function dropDownMenu() {
    var x = document.getElementById("topNavClick");
    if (x.className == "topNav") {
        x.className += " responsive";
    } else {
        x.className = "topNav";
    }
}

function displayRegister() {
    var x = document.getElementById("registerForm");
    x.style.display = "block";
}

function displayAdvancedSearch() {
    var x = document.getElementById("simpleSearch");
    x.style.cssText += "border-radius: 5px; margin-bottom:10px;";
    var x = document.getElementById("advancedSearch");
    x.style.display = "block";
}

//cancel reservation
function cancelReservation(){
    let _ = confirm("Are you sure you want to cancel the reservation process?")
    if(_){
        window.location.replace('./index.php?cancelBook=1');
    }
}

//description character counter
function countChar(){
    let desc = document.getElementById("description");
    let count = document.getElementById("count");

    count.innerHTML = desc.value.length + " / " + desc.getAttribute('maxlength');
}

function returnHome(){
    window.location.replace('./index.php');
}

function deleteReservation(){
    let _ = confirm("Are you sure you want to cancel your reservation?")
    if(_){
        window.location.replace('./reservationCancel.php?confirm=1');
    }else{
        window.location.replace('./reservationView.php')
    }
}
