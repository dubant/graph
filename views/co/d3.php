<!DOCTYPE html>
<meta charset="utf-8">

<?php
 $cs = Yii::app()->getClientScript();
  $cs->registerScriptFile(Yii::app()->request->baseUrl. '/plugins/d3/d3.v4.min.js' );
?>

<style>

.links line {
  stroke: #999;
  stroke-opacity: 0.6;
}

.nodes circle {
  stroke: black	;
  stroke-width: 1px;
}

#graph{
    float: left;
    width:600px;
}
#graphtags{
    padding-top: 20px;
    background-color: #fff;
    width:0%;
    float: left;
    height:600px;
    width:20%;
    
}
#graphtags a{
    color: #333;
    text-decoration: none;
}
#sectionList a{
    color: red;
    text-decoration: none;
}
#search{
    float:right;
    margin-right: 100px;
}
#title{
    background-color: #eee;
    height:80px;
    font-size: 2em;
    padding:15px;
}
</style>
<div id='title'>

<?php
$l = $title;
if( !empty( @$link ) )
    $l = '<a class="lbh" data-dismiss="modal" href="'.$link.'">'.$title. ' <i class="fa fa-link"></i></a>';
?>
    <div class='pull-left'><?php echo $l?></div>
    <input id='search' type='text' placeholder='#tag, free search, >types' onkeypress='return runScript(event)'/>
</div>

<div  id="graphtags" class="hide">
    <div id="sectionList"></div>
</div>

<svg id="graph" height="600" ></svg>

<script>

function runScript(e) {
    if (e.keyCode == 13) {
        s = document.getElementById("search").value;
        if (s.indexOf("#") == 0 )
            open("graph/co/search/tag/"+s.substring(1) );
        else if (s.indexOf(".") == 0 )
            open("graph/co/search/geo/"+s.substring(1) );
        else if (s.indexOf(">") == 0 )
            open("graph/co/search/type/"+s.substring(1) ) ;
        else
            open("graph/co/search/q/"+s );
    }
}
function open (url) {
    //alert(url);
    if(typeof $ != "undefined")
        smallMenu.openAjaxHTML( baseUrl+'/'+url);
    else
    // REMETTRE "/ph/"+url; en prod
        window.location.href = "/ph/"+url;
}
//create somewhere to put the force directed graph

var baseUrl = "<?php echo Yii::app()->getRequest()->getBaseUrl(true);?>";

var svg = d3.select("svg"),
    width = +svg.attr("width"),
    height = +svg.attr("height");

//add zoom capabilities
svg.call(d3.zoom().on("zoom", zoom_actions))

var radius = 15;
var image_default = "";//https://github.com/favicon.ico";

console.log( "data", <?php echo json_encode($data); ?>);
console.log( "list", <?php echo json_encode(@$list); ?>);
console.log( "links", <?php echo json_encode($links); ?>);
var tags = <?php echo json_encode($tags); ?>;
var nodes_data = <?php echo json_encode($data); ?>;
var links_data = <?php echo json_encode($links); ?>;

//set up the simulation and add forces
var simulation = d3.forceSimulation()
					.nodes(nodes_data);

var link_force =  d3.forceLink(links_data)
                        .id( function(d) { return d.id; } )
                        .strength( function (d) { return d.strength; } );

var charge_force = d3.forceManyBody()
    .strength(-120);

var center_force = d3.forceCenter(width / 2, height / 2);

simulation
    .force("charge_force", charge_force)
    .force("center_force", center_force)
    .force("links",link_force)
 ;

//add tick instructions:
simulation.on("tick", tickActions );

//add encompassing group for the zoom
var svg_g = svg.append("g")
    .attr("class", "everything")

//draw lines for the links
var svg_g_g = svg_g.append("g")
      .attr("class", "links")
    .selectAll("line")
    .data(links_data)
    .enter()

var svg_g_g_line = svg_g_g.append("line")
    .attr("stroke-width", 5)
    .style("stroke", linkColour);

//draw circles for the nodes
var svg_g_g = svg_g.append("g")
        .attr("class", "node")
        .selectAll(".node")
        .data(nodes_data)
        .enter()

var svg_g_g_circle = svg_g_g.append("circle")
        .attr("r", circleSize)
        .attr("fill", circleColour)
        .on('click', selectNode)
        //add drag capabilities on circle
        .call(d3.drag()
        	.on("start", drag_start)
        	.on("drag", drag_drag)
        	.on("end", drag_end))


var svg_g_g_image = svg_g_g.append("image")
      .attr("xlink:href", function (d){
      if (d.img == "undefined" || !d.img)
        return image_default;
      else {
        return d.img;
      }
      })
      .attr("width", 16 )
      .attr("height", 16 )
      .on('click', selectNode)
      //add drag capabilities on image
      .call(d3.drag()
        .on("start", drag_start)
        .on("drag", drag_drag)
        .on("end", drag_end))

var svg_g_g_text = svg_g_g.append('text')
        .text(function (node) { return node.label })
        .attr('font-size', 20)
        .attr('dx', 15)
        .attr('dy', 4)

/** Functions **/

//Function to choose what color circle we have
//Let's return blue for males and red for females
function circleColour(d){

    if(d.type == "tag")
        return "steelblue";
    else if(d.type == "event" || d.type == "event")
        return "#FFA200";
    else if(d.type == "project" || d.type == "projects")
        return "purple";
    else if( d.type == "organization" || d.type == "organizations" )
        return "#93C020";
    else if(d.type == "citoyens" || d.type == "citoyen" )
        return "#FFC600";

    if(d.level ==0){
        return "black";
    }else if(d.level ==1){
		return "#c62f80";
	} else {
		return "#cccccc";
	}
}

function circleSize(d){
    r = 10;
    if(d.level ==1 || d.level == 0)
        return 20;
    if(d.linkSize > 0)
        r += d.linkSize;
    if(r>30)
        r = 30;
    //console.log("radius", r, d.linkSize);
    return r;
}

//Function to choose the line colour and thickness
//If the link type is "A" return green
//If the link type is "E" return red
function linkColour(d){
	if(d.type == "A"){
		return "green";
	} else {
		return "#333333";
	}
}

//Drag functions
//d is the node
function drag_start(d) {
 if (!d3.event.active) simulation.alphaTarget(0.3).restart();
    d.fx = d.x;
    d.fy = d.y;
}

//make sure you can't drag the circle outside the box
function drag_drag(d) {
  d.fx = d3.event.x;
  d.fy = d3.event.y;
}

function drag_end(d) {
  if (!d3.event.active) simulation.alphaTarget(0);
  d.fx = null;
  d.fy = null;
}

//Zoom functions
function zoom_actions(){
    svg_g.attr("transform", d3.event.transform)
}

function tickActions() {
    //update circle positions each tick of the simulation
    svg_g_g_circle
      .attr("cx", function(d) { return d.x; })
      .attr("cy", function(d) { return d.y; });

    svg_g_g_image
      .attr('x', function (d) { return d.x-8 })
      .attr('y', function (d) { return d.y-8 })

    svg_g_g_text
      .attr('x', function (d) { return d.x })
      .attr('y', function (d) { return d.y })

    //update link positions
    svg_g_g_line
        .attr("x1", function(d) { return d.source.x; })
        .attr("y1", function(d) { return d.source.y; })
        .attr("x2", function(d) { return d.target.x; })
        .attr("y2", function(d) { return d.target.y; });
}

function selectNode(selectedNode) {
    console.log(selectedNode);
    types = ["citoyen", "organization", "project", "event"];
    if(selectedNode.level == 0)
        return;
    else if(selectedNode.id == "tags" ){
        $("#graphtags").css("width","20%");
        $("#graph").css("width","80%");
        $("#graphtags").toggleClass("hide");
    } else if(selectedNode.level == 1  ){
        $("#graphtags").css("width","20%");
        $("#graph").css("width","80%");
        $("#graphtags").toggleClass("hide");
        document.getElementById("sectionList").innerHTML = "<b>"+selectedNode.label+"</b><br/>";

        links_data.forEach(function (t) {
        if (t.source.id == selectedNode.id){
            document.getElementById("sectionList").innerHTML += "<a href='javascript:open(\"graph/co/d3/id/"+t.target.id+"/type/"+t.target.type+"\")'> "+t.target.label+"</a><br/>";
        }

        })
        document.getElementById("sectionList").innerHTML += "<br/><br/>"
    }
    else if(selectedNode.id.length > 20 ){
        open( "graph/co/d3/id/"+selectedNode.id+"/type/"+types[selectedNode.group-1] );
        //alert( selectedNode.id+"/"+(selectedNode.group-1)+"/"+types[selectedNode.group-1] );
    }
    else if (selectedNode.type == "tag")
        open( "graph/co/search/tag/"+selectedNode.label );
}

if(typeof $ != "undefined")
    $("#graph").css("width","78%")
else
    document.getElementById("graph").style.width = "78%";

tags.forEach(function (t) {
    if (t != "") {
            document.getElementById("graphtags").innerHTML = document.getElementById("graphtags").innerHTML+ "<a href=\"javascript:open('graph/co/search/tag/"+t+"')\">#"+t+"</a><br/>";
    }
  })

if(typeof $ != "undefined")
    bindLBHLinks();

</script>
