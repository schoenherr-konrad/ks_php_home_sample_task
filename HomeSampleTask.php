<?php
/*
Home Sample task - SilverTours GmbH
Author: Konrad Schönherr
Date: 30.01.2021
Requires: PHP 8.0
External libraries: Bootstrap

1. Task

To manage a stock of goods, articles are stored in a class as follows:

class Article
{
    string name
    int group
    float price
}


The name attribute specifies a description of the item, the group attribute specifies the item group, and the price attribute specifies the price.

A class is to be designed which receives an array or a collection of articles as input parameters. The method should then return a new array or a new collection that summarizes all articles with the same group, adds the price, and summarizes the names comma-separated to a new name. No name should appear twice in the new comma separated name.

An exception are articles of the group 0, these should not be combined. Ideally, the result should be sorted by group, as the following example illustrates.

2. Task

To get more flexibility the code should be refactorized as described below.

2.1 It should be possible to retrieve the results sorted by name, price ascending or price descending.
2.2 Instead of combining articles by group we want to combine articles by a business rule that we do not know yet. How can we achieve that? How can we test this?
2.3 In order to support multiple countries, it should be possible to specify the currency for each price. What must be considered here?

*/

class Article
{
	public string $name;
	public int $group;
	public float $price;

	public function __construct(string $name, int $group, float $price) {
		$this->name  = $name;
		$this->group = $group;
		$this->price = $price;	
	}

}

//Class that allows aggregate "name" or "group"
class GroupedArticle
{
        private array $namelist;
        private array $grouplist;
        private float $price;

        public function __construct(array $namelist, array $grouplist, float $price) {
                $this->namelist     = $namelist;
                $this->grouplist    = $grouplist;
                $this->price        = $price;
        }

	public function inList(string $col, $needle) {
		$col_list=$col."list";
		return in_array($needle,$this->$col_list);
	}

	public function addArticle(Article $art, $aggregate_col="name") {
		foreach(["name","group"] as $col) {
			$col_list=$col."list";
			if ($aggregate_col==$col) {
        			if(!in_array($art->$col,$this->$col_list)) {
               				array_push($this->$col_list,$art->$col);
                		}
			} else {
				$this->$col_list=array($art->$col);
			}
		}
                $this->price += $art->price;
	}

	public function getNamelist() {
		return implode(",",$this->namelist);
	}
	
	public function getGrouplist() {
		return implode(",",$this->grouplist);
	}

        public function getPrice() {
		return $this->pricelist;  
        }

	//formatted Output for money - standard is Euro
        public function getPriceFormatted(string $format="%0.2f €") {
                return sprintf($format,$this->price);
        }
}

//Comparision Callbacks used by usort

function cmp_std($a,$b) {
	return $a->getGrouplist() <=> $b->getGrouplist();
};
function cmp_name($a,$b) {
        return strnatcmp($a->getNamelist(),$b->getNamelist());
};
function cmp_price_asc($a,$b) {
        return $a->getPrice() <=> $b->getPrice();
};
function cmp_price_desc($a,$b) {
        return $b->getPrice() <=> $a->getPrice();
};

class ArticleList
{
	private array $articles;
        
	public function __construct(array $articles) {
                $this->articles=$articles;
	}


	public function query(string $cmp_func="cmp_std",string $groupby_col="group", string $aggregate_col="name",string $not_aggregate_if_col = "group",$not_aggregate_value=0) {
		$output_array=array();
		foreach($this->articles as $article) {
			if($article->$not_aggregate_if_col!=$not_aggregate_value) { //implementation of "NOT AGGREGATE" if column value a specific value
                        	foreach($output_array as &$grouped_article) {
                                	if($grouped_article->inList($groupby_col,$article->$groupby_col)) {
						$grouped_article->addArticle($article,$aggregate_col);
                                        	continue 2; //continue 2nd foreach loop
                                	}
                        	}
			};	
			$output_array[]=new GroupedArticle(array($article->name),array($article->group),$article->price);				
                }
		usort($output_array,$cmp_func);
		return $output_array;
	}
	
}

//initial data setup
$articles = new ArticleList(array(
	new Article('AA',1,100.00),
        new Article('BB',1,50.00),
        new Article('CC',2,75.00),
        new Article('AA',1,20.00),
        new Article('AA',0,100.00),
        new Article('BB',2,75.00),
        new Article('CC',2,80.00),
        new Article('AA',0,20.00)
));

//task result generation
$results["Task 1"]=array("articles"=>$articles->query());
$results["Task 2.1 sorted by name"]=array("articles"=>$articles->query("cmp_name"));
$results["Task 2.1 sorted by price asc"]=array("articles"=>$articles->query("cmp_price_asc"));
$results["Task 2.1 sorted by price desc"]=array("articles"=>$articles->query("cmp_price_desc"));
$results["Task 2.2 user defined grouped by name"]=array("articles"=>$articles->query("cmp_name","name","group")); // Example für a user defined "group by" more flexibility would we have using an ORM like Doctrine 
$results["Task 2.3 USD instead"]=array("articles"=>$articles->query(),"money_format"=>'$ %0.2f'); // option money_format for other money format (Task 2.3)
?>
<header>
<!-- add Bootstrap CSS for better table layout -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
<!-- JavaScript Bundle for Bootstrap with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
</header>
<body>
<?php foreach ($results as $taskname=>$result) { ?>
<h2><?php echo $taskname ?></h2>
<table class="table">
  <thead>
    <tr>
      <th scope="col">Name</th><th scope="col">Group</th><th scope="col">Price</th>
    </tr>
  </thead>
  <tbody>
<?php foreach ($result["articles"] as $row) { ?>
    <tr>
      <td><?php echo $row->getNamelist() ?></td><td><?php echo $row->getGrouplist() ?></td><td><?php echo isset($result["money_format"])?$row->getPriceFormatted($result["money_format"]):$row->getPriceFormatted(); ?></td>
    </tr>
<?php } ?>
  </tbody>
</table>
<?php } ?>
</body>

