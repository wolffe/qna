<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url):?>
   <url>
      <loc><?php echo $url['loc'];?></loc>
      <lastmod><?php echo date('c', $url['lastmod']);?></lastmod>
      <changefreq><?php echo $url['changefreq'];?></changefreq>
      <priority><?php echo $url['priority'];?></priority>
   </url>
<?php endforeach;?>
</urlset>