<?php

include_once ("Khooj.php");

$ary = json_decode('[
   {
      "lfs" : [
         {
            "freq" : 15893,
            "lf" : "body mass index",
            "since" : 1978,
            "vars" : [
               {
                  "freq" : 14076,
                  "lf" : "body mass index",
                  "since" : 1978
               },
               {
                  "freq" : 1004,
                  "lf" : "Body mass index",
                  "since" : 1981
               },
               {
                  "freq" : 497,
                  "lf" : "Body Mass Index",
                  "since" : 1983
               }
            ]
         },
         {
            "freq" : 113,
            "lf" : "bicuculline methiodide",
            "since" : 1980,
            "vars" : [
               {
                  "freq" : 104,
                  "lf" : "bicuculline methiodide",
                  "since" : 1981
               },
               {
                  "freq" : 8,
                  "lf" : "Bicuculline methiodide",
                  "since" : 1980
               },
               {
                  "freq" : 1,
                  "lf" : "bicuculline-methiodide",
                  "since" : 1996
               }
            ]
         }
      ],
      "sf" : "BMI"
   }
]',true);

// Find Data By Query
$freq = Khooj::find( '0/lfs/[freq=>{f}]/vars/[since=>{s}]', $ary, array(
	'{f}'=>15893,
	'{s}'=>1978
));

//Since attribute
#echo $freq['since'];

// Manually remove element of node
unset($freq['lf']);

// Add New Elements
$freq['untitled']='Dummy Value';
$freq['dir']=array(
	'foo'=>'bar.ext',
	'path'=>array(
		'to'=>'/some/dir/name'
	)
);

//Query for ( [0]->lsf[0]->vars[1]->lf )
#1: 0/lfs/0/vars/1/lf  (Direct)
#2: 0/lfs[0]/vars/[freq=>1004]/lf
#2: 0/lfs[0,vars]/[freq=>1004]/lf (index/list[index,nodes]/[key=>value]/lf)
#3: 0/lfs[0,vars][freq=>1004]/lf (index/list[index,nodes][key=>value]/attribute)

// Update
$fre = Khooj::update( '0/lfs/[freq=>{f}]/vars[since=>{s}]', $ary, $freq, array(
	'{f}'=>15893,
	'{s}'=>1978
));


// Delete attribute
Khooj::delete( '0/lfs[0,vars][freq=>1004]/lf', $ary );

// Display updated ary
echo '<pre>';
print_r($ary);
echo '</pre>';
