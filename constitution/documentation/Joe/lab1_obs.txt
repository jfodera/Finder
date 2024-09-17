Lab 1 notes: 



Plan: 

Website with the constitution: https://constitution.congress.gov/constitution/
   supposed to copy and paste the articles into our site 
Annotate the articles and amendments only 
   in these annotations "cite how it relates to your lives today" 
   paraphrase annotations from other websites 
   Our annotation Format roughly: https://law.justia.com/constitution/us/article-3/06-legislative-courts.html#fn-119
   important to note that could have used javascript for the anchor links but html had them built in 
History Page: 
   outlining the history of the constitution
   interactive timeline where you click on and it gives you more info 

HTML, CSS, and Javascript


to do: 
make shared azure √
Annotate sections 1-4, amendments 8-19 √
Amendments √
Look over Dereks code √
Start making annotation code√
fix js edits so that everything opens and closes properly √
Validate all pages √
   no warnings!
Make sure CSS sheet is divided up and labled properly√
when coding is done, restructure files so index.html is first in line


id naming conventions: 
   origin statement: id="ar1-1o" article 1, annotation 1, origin
   annotation statment (child) : id="ar1-1ch" article 1, annotation 1, child
   <li id= "am2-1ch"><a href="#am2-1o" class="super">1</a>
   </li>

   <a href="#am27-1ch" id="am27-1o" class="super">1</a>

Things I learned: 
Anchor Tags
lots of linux commands to get my vm directories set up correctly 
lots of history regarding hte constitution and how it relates back to today 


Future improvements: 
   Could probably make it so that website is built dynamically and I didn't need to tag each annotation induvidually 


Any challenges I came across: 
The main thing I had trouble with was figuring out a good format for the annotations and how to implement them as efficently as possible. At first I was 
considering using a combonation of js listening functions but then I read through some documentation on W3: https://www.w3schools.com/tags/tag_a.asp
and found out that I can use an anchortag to travel to a certain instance in a page. I then created the naming conventions seen above and thus set a good 
structure to keep consitency throughout the page. The process of figuring that out was the greatest struggle but I'm glad I did. 

Also good to note that I did a decent amount of code review here, reading others comments and trying to figure out what they did which I'm defintley 
happy I got some good practice with. 
