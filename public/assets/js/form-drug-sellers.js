$(document).ready(function() {



    $("#location-picker").click(function(e) {
        getLocation();
    });


    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(showPosition);
        } else {
            alert("Failed to pick location. Please allow this browser to share location and try again.");
        }
    }

    function showPosition(position) {

        $('#latitude').val(position.coords.latitude);
        $('#longitude').val(position.coords.longitude);

    }

});