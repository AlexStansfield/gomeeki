var map;

// Initialize the map
function initialize() {
    var mapOptions = {
        zoom: 11,
        center: new google.maps.LatLng(latitude, longitude)
    };

    var infowindow = new google.maps.InfoWindow({
        content: "loading..."
    });

    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

    setTweets(map, tweets, infowindow);
}

// Add the tweets to the map
function setTweets(map, tweets, infowindow) {
    for (var i = 0; i < tweets.length; i++) {
        var tweet = tweets[i];
        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(tweet[2], tweet[3]),
            map: map,
            title: tweet[0],
            icon: tweet[1],
            html: tweet[4],
            zIndex: i
        });

        google.maps.event.addListener(marker, "click", function () {
            infowindow.setContent(this.html);
            infowindow.open(map, this);
        });
    }
}

// Do a search when form submitted (either by pressing enter in input or clicking search)
$("#formSearch").submit(function() {
    window.location.href = '/search/' + $("#inputSearch").val();
    return false;
});
// Go to history page when button pressed
$("#buttonHistory").click(function() {
    window.location.href = '/history';
});

// Resize the map canvas according to form height
$(window).resize(function(){
    var rowHeight = $(".row").height();
    var mapHeight = $(window).height() - rowHeight;

    $('#map-container').height(mapHeight);
})

google.maps.event.addDomListener(window, 'load', initialize);

// Trigger the window resize
$(window).resize();