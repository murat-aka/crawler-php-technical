<?php 

/**
 * Path         : Crawler.php
 * 
 * Created      : 20/04/2018
 * LastEdit     : 20/04/2018
 * @Author      : Murat Aka
 * 
 * @desc-
 * 
 * <br> System  : PHP
 * <br> Purpose : fetch data from website. 
 * 
 * <br>Amendments
 * <br>----------
 * <br>20/04/2018 - Murat Aka -
 * 
 * 
 */

/******************************************************************************
* CRAWLERCLASS.PHP                                                            *
*                                                                             *
* Version: 1.0                                                                *
* Date:    20/04/2018                                                         *
* Author:  Murat Aka                                                          *
*******************************************************************************/


$CRAWLER_object = new CRAWLER("http://www.black-ink.org");

/**
 * Class CrawlerClass
 *
 * Used to fetch website data.
 */
class CRAWLER
{

    
    /**
     * @var string $site
     *
     * Holds website address
     * 
     */
    private $domain = "";
    

    /**
     * @var string $output_file
     *
     * Name of the output file
     * 
     */
    private $output_file = "results.txt";
    
    /**
     * @var string $content
     *
     * Content to search for existence
     * 
     */
    private $content = "Digitalia";
    
    
    /**
     * @var array $found_data
     *
     * Holds final results
     * 
     */
    private $found_data = array();
    
    
    
    
    /**
     * @var int $output_file
     *
     * Maximum urls to check
     * 
     */
    private $max_urls_to_check = 1;
    
    
    /**
     * @var int $output_file
     *
     * Counter for number of urls checked
     * 
     */    
    private $rounds = 0;
    

    /**
     * @var int $max_size_domain_stack
     *
     * Maximum size of domain stack
     * 
     */
    private $max_size_domain_stack = 10;
    

    

/********************************************************************************
*                           Constructors  and Destructor                       *
*******************************************************************************/   
    public function CRAWLER($address){
      
     
      $this -> setWebsiteName($address);
      $this -> websiteCrawler();
      

    }


    public function __destruct()
    {
      //exit();
    }

/********************************************************************************
 *                             Getters and Setters                              *
 *******************************************************************************/



    /**
     * set site 
     *
     * @param string $newval website address 
     * 
     */

    public function setWebsiteName($newval)
    {
      $this->domain = $newval;
    }


    /**
     * returns site
     *
     * @return string site
     */ 

    public function getWebsiteName()
    {
      return $this->domain;
    }    



/********************************************************************************
 *                               Private methods                                *
 *******************************************************************************/


   /**
     * writes content to an output file
     *
     * @param string $path output file directory
     * @param string $content text to write to the file
     */
    private function writeFile($path,$content){
        
        try {
            // Write string to specified output file
            file_put_contents($path, $content);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

   
    }
    


    
/********************************************************************************
 *                               Protected methods                              *
 *******************************************************************************/



/********************************************************************************
 *                               Public methods                                 *
 *******************************************************************************/
 
    
   /**
     * Finds post links of the given website i.e www.black-ink.org
     * and writes the output to a file
     * 
     */    
    public function websiteCrawler(){
        
        
        if($this->domain == ""){
            
            
            die("Empty Domain!");
        }
        
        
        $results = array();
        $total = 0;

        // Loop through the domains as long as domains are available
        // and the maximum number of urls to check is not reached
        while ($this->rounds <= $this-> max_urls_to_check -1) {
            
            // New document object to hold domain content  
            $doc = new DOMDocument();
            
            // Get the sourcecode of the domain
            @$doc->loadHTMLFile($this -> domain . '/page/' . $this -> rounds);
            
            // Get article nodes from the document 
            $articles = $doc->getElementsByTagName('article');

            // Loop through articles 
            foreach($articles as $x => $article) {
                
                // Single article node as text string
                $article_node = $this -> get_inner_html($article);
                
                // Filter articles with the search criteria of <italic>Digitalia</italic> 
                if (strpos($article_node, $this->content) !== false){
                    
                        // Look for list within the article that will contain the subdomain of the article
                        $li_elements = $article->getElementsByTagName('li');
                        // The first list correspondes to the subdomain of the article
                        $li_element = $li_elements -> item(0);
                        
                        // Get the hyperlinks from within list
                        $a_elements = $li_element->getElementsByTagName('a');
                        // Get the subdomain address from the first hyperlink that has the article site
                        $link = $a_elements -> item(0);
                        $href = $link->getAttribute('href');
                        
                        
                        //Get the hyperlink text
                        $link_text = $link->nodeValue;
                        
                        // Add found hyperlink to the hash
                        $result['url'] = $href;
                        // Add found hyperlink text to the hash
                        $result['link'] = $link_text;
                        
                        // Get url tags
                        $tags = get_meta_tags($href);
                        
                        // Get description from the meta data
                        $result['meta description'] = $tags['description'];
                        
                        // Get tags from the meta data
                        $result['keywords'] = $tags['keywords'];

                        //Get hyperlink bytes
                        $bytes = $this -> getRemoteFilesize($href);
                        // Get filesize of the hypertext
                        $result['filesize'] = $this -> convertBytes($bytes);
                        
                                                
                        // Add file size to the previous total
                        $total += $bytes;
                        
                        // push current result to the end of array
                        array_push($results,$result);

                }

            }

            // Add results to the hash 
            $this -> found_domains['results'] = $results;
            
            // Add total size to the hash
            $this -> found_domains['total'] = $this -> convertBytes($total);
            
            // Increment counter
            $this -> rounds++;

        }
         
        // Convert hash to a JSON string
        echo $string_data = json_encode($this -> found_domains);
        
        // Write data to a file
        $this -> writeFile($this -> output_file, $string_data);
        
    }      
    
    
    
   /**
     * writes buffer to the screen
     *
     */    
    public function printScreen(){
        
        ob_flush();
        flush();
        ob_clean();
    }
    
   /**
     * returns utf8_decoded text of childNodes
     *
     * 
     * @return string $innerHTML
     */    
    public function get_inner_html( $node ) { 
        $innerHTML= ''; 
        $children = $node->childNodes; 
        foreach ($children as $child) { 
            $innerHTML .= $child->ownerDocument->saveHTML( $child ); 
        } 
    
        return utf8_decode($innerHTML); 
    }  
    
    
   /**
     * Gets remote url size
     *
     * @return int $size url size
     */    
    public function getRemoteFilesize($file_url, $formatSize = true)
    {
        $head = array_change_key_case(get_headers($file_url, 1));
        // content-length of download (in bytes), read from Content-Length: field
    	
        $clen = isset($head['content-length']) ? $head['content-length'] : 0;
     
        // cannot retrieve file size, return "-1"
        if (!$clen) {
            return -1;
        }
     
        if (!$formatSize) {
            return $clen; 
    		// return size in bytes
        }
     
        $size = $clen;
        return $size; 
    	
    }
    

   /**
     * Converts given bytes to KB, MB and GB
     *
     * @return string $size 
     */     
    public function convertBytes($clen){
        
        $size = $clen;
        switch ($clen) {
            case $clen < 1024:
                $size = $clen .' B'; break;
            case $clen < 1048576:
                $size = round($clen / 1024, 2) .' KB'; break;
            case $clen < 1073741824:
                $size = round($clen / 1048576, 2) . ' MB'; break;
            case $clen < 1099511627776:
                $size = round($clen / 1073741824, 2) . ' GB'; break;
        }
        
        // return formatted size
        return $size;
        
    }
    
     
        
}

    
    
    

