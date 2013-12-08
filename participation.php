<?php

// get the data from the API

$url = "http://test.api.joind.in/v2.1/events?filter=past&verbose=yes";
$dataSet = array();

while(true) { // we'll break out when we've had enough
    $results_json = file_get_contents($url);
    $results = json_decode($results_json, true);
    $events = $results['events'];
    foreach($events as $event) {
        // too few attendees?  move on
        if($event['attendee_count'] < 10) {
            continue;
        }
        $data = array();
        $data['event'] = $event['name'];
        $date = strtotime($event['start_date']);
        if(date('Y', $date) < 2013) {
            // stop - jump out of foreach *and* while
            break 2;
        }
        $data['date'] = date('F Y', $date);
        $data['continent'] = $event['tz_continent'];
        $data['place'] = $event['tz_place'];

        $data['attendees'] = $event['attendee_count'];
        $data ['comments'] = $event['talk_comments_count'];

        // get talks
        $talks_json = file_get_contents($event['talks_uri'] . '?resultsperpage=0');
        $talks = json_decode($talks_json, true);
        $data['talks'] = $talks['meta']['count'];

        // calculate the attendee-to-comment ratio
        $data['comments_per_attendee'] = $data['comments'] / $data['attendees'];
        $data['comments_per_talk'] = $data['comments'] / $data['talks'];

        $dataSet[] = $data;
    }
    $url = $results['meta']['next_page'];
}

// now start the HTML template
?>

<style>
    text {
        font-size: 12 !Important;
    }
</style>

<script type="text/javascript">
    var data = <?=json_encode($dataSet)?>;
</script>

<div id="chartContainer">
  <script src="http://d3js.org/d3.v3.min.js"></script>
  <script src="http://dimplejs.org/dist/dimple.v1.1.2.min.js"></script>
  <script type="text/javascript">
    var svg = dimple.newSvg("#chartContainer", 600, 400);
    var myChart = new dimple.chart(svg, data);
    myChart.addMeasureAxis("x", "attendees");
    myChart.addMeasureAxis("y", "comments");
    myChart.addSeries(["event", "talks", "attendees", "comments", "place"], dimple.plot.bubble);
    myChart.draw();
  </script>
</div>

<div id="ratioChartContainer">
  <script src="http://d3js.org/d3.v3.min.js"></script>
  <script src="http://dimplejs.org/dist/dimple.v1.1.2.min.js"></script>
  <script type="text/javascript">
    var svg = dimple.newSvg("#ratioChartContainer", 600, 400);
    var myChart = new dimple.chart(svg, data);
    myChart.addMeasureAxis("x", "talks");
    myChart.addMeasureAxis("y", "comments");
    myChart.addMeasureAxis("z", "comments_per_talk");
    myChart.addSeries(["event", "talks", "attendees", "comments", "place"], dimple.plot.bubble);
    myChart.draw();
  </script>
</div>

<div id="leagueChartContainer">
  <script src="http://d3js.org/d3.v3.min.js"></script>
  <script src="http://dimplejs.org/dist/dimple.v1.1.2.min.js"></script>
  <script type="text/javascript">
    var svg = dimple.newSvg("#leagueChartContainer", 600, 800);
    var myChart = new dimple.chart(svg, data);
    myChart.setMargins(250,50,50,50);
    myChart.addMeasureAxis("x", "comments_per_talk");
    var y = myChart.addCategoryAxis("y", "event");
    y.addOrderRule("comments_per_talk")
    myChart.addSeries(null, dimple.plot.bar);
    myChart.draw();

  </script>
</div>

