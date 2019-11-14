
 <div class="panel">		  
	<div clas="col-12"> 
		{foreach from=$all_news item=t}

			<div class="row">
				<div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
					{l s='Name' mod='newsletter_shop'}: {$t.name}
				</div>
				<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12" style="text-align:left;">
					{l s='Email' mod='newsletter_shop'}: {$t.email}
				</div>
			</div>

		{/foreach}
	</div>
	</div>
</div>
