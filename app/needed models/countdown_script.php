<?php
date_default_timezone_set('Africa/Lagos');
$today = date('Y-m-d');
$cutoffTime = $today . ' 20:30:00'; // 8:30 PM
?>

<h4 class="text-red-500 leading-7 font-semibold text-sm">
    <strong>
        <span id="text_notice"></span>
        <span id="counter" class="blinking"></span>
    </strong>
</h4>

<script>
// Set the daily cutoff time (8:30 PM)
var countDownDate = new Date("<?= $cutoffTime ?>").getTime();

// Update every second
var x = setInterval(function () {
    var now = new Date().getTime();
    var distance = countDownDate - now;

    // Time calculations
    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // Display countdown
    if (distance > 0) {
        document.getElementById("text_notice").innerHTML = "ATTENTION: Posting will be automatically disabled in ";
        document.getElementById("counter").innerHTML = hours + "h " + minutes + "m " + seconds + "s ";
    } else {
        clearInterval(x);
        document.getElementById("text_notice").innerHTML = "ATTENTION: TIME UP! Posting disabled.";
        document.getElementById("counter").innerHTML = "";
    }
}, 1000);
</script>
