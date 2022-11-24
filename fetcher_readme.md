1. install https://www.drupal.org/project/feeds_fetcher_headers
2. activate (like lando drush en...)feeds_fetcher_headers.
3. install https://www.drupal.org/project/key
4. activate Key module as usual (drush or from UI)
5. go to the https://your_site/admin/config/system/keys and set two keys (please be carefull with capital letters and spaces, follow to the screenshots):

### Client_Id

### Client_Secret

6. copy the code from GitHub file, then go to the ~modules/contrib/feeds_fetcher_headers/src/Feeds/Fetcher/HttpFetcherHeaders.php and replace all code
7. lando drush cr
8. go to the feed which you are working with and put inside of "Scope" some of:<br/>
   customers:read<br/>
   invoices:read<br/>
   projects:read<br/>
   etc.
   save and make an import.
9. hope it helps...
10. the code in the process of deployment as a patch. Please be condenscending.
