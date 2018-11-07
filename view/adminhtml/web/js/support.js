requirejs(['algoliaAdminBundle'], function(algoliaBundle) {
	algoliaBundle.$(function ($) {
		handleLatestVersion($);
		
		const documentationSearch = algoliaBundle.instantsearch({
			appId: 'BH4D9OD16A',
			apiKey: 'a23cdc99940ffad43a4f98733b845fdf',
			indexName: 'magento_algolia',
			searchParameters: {
				filters: 'NOT tags:m1'
			}
		});
		
		const discourseSearch = algoliaBundle.instantsearch({
			appId: 'G25OKIW19Q',
			apiKey: '7650ddf6ecb983c7cf3296c1aa225d0a',
			indexName: 'discourse-posts',
			searchParameters: {
				filters: 'topic.tags: magento'
			}
		});
		
		documentationSearch.addWidget(getSearchBoxWidget());
		
		documentationSearch.addWidget(
			algoliaBundle.instantsearch.widgets.hits({
				container: '.search_results.doc',
				templates: {
					item: getDocumentationTemplate()
				}
			})
		);
		
		discourseSearch.addWidget(getSearchBoxWidget());
		
		discourseSearch.addWidget(
			algoliaBundle.instantsearch.widgets.hits({
				container: '.search_results.forum',
				templates: {
					item: getDiscourseTemplate()
				},
				transformData: {
					item: function(hit) {
						hit.content = escapeHighlightedString(
							hit._snippetResult.content.value
						);
						
						hit.tags = hit._highlightResult.topic.tags;
						
						return hit;
					}
				}
			})
		);
		
		documentationSearch.start();
		discourseSearch.start();
	});
	
	function handleLatestVersion($) {
		$.getJSON('https://api.github.com/repos/algolia/algoliasearch-magento-2/releases/latest', function(payload) {
			var latestVersion = payload.name;
			
			if(compareVersions(algoliaSearchExtentionsVersion, latestVersion) > 0) {
				$('.legacy_version').show();
			}
		});
	}
	
	function getSearchBoxWidget() {
		return algoliaBundle.instantsearch.widgets.searchBox({
			container: '#search_box',
			placeholder: 'Search for help',
			reset: false,
			magnifier: false
		});
	}
	
	function getDocumentationTemplate() {
		return `
		<div class="ais-result">
			{{#hierarchy.lvl0}}
				<div class="ais-lvl0">
					{{{_highlightResult.hierarchy.lvl0.value}}}
				</div>
			{{/hierarchy.lvl0}}
		
			<div class="ais-lvl1">
				{{#hierarchy.lvl1}}
					{{{_highlightResult.hierarchy.lvl1.value}}}
				{{/hierarchy.lvl1}}
				
				{{#hierarchy.lvl2}}
					 > {{{_highlightResult.hierarchy.lvl2.value}}}
				{{/hierarchy.lvl2}}
				{{#hierarchy.lvl3}}
					> {{{_highlightResult.hierarchy.lvl3.value}}}
				{{/hierarchy.lvl3}}
				
				{{#hierarchy.lvl4}}
					> {{{_highlightResult.hierarchy.lvl4.value}}}
				{{/hierarchy.lvl4}}
			</div>
			
			<div class="ais-content">
				{{{#content}}}
					{{{_highlightResult.content.value}}}
				{{{/content}}}
			</div>
		</div>`;
	}
	
	function getDiscourseTemplate() {
		return `
		<div class="result">
			<a href="https://discourse.algolia.com{{url}}" target="_blank" class="result-link">
				<div class="result-title">
					<div>
						{{{_highlightResult.topic.title.value}}}
						<img width="12" height="12" src="{{external_link_src}}">
					</div>
				
					<div>
						<span class="result-title-tag radius4">
							{{category.name}}
						</span>
						
						{{#tags}}
							{{#value}}
								<span class="result-title-tag radius4">
									{{{value}}}
								</span>
							{{/value}}
						{{/tags}}
					</div>
				</div>
				
				<div class="result-hierarchy">
					{{user.username}}
				</div>
				
				<div class="result-content">
					{{{content}}}
				</div>
			</a>
		</div>`;
	}
	
	function escapeHighlightedString(str, highlightPreTag, highlightPostTag) {
		highlightPreTag = highlightPreTag || '<em>';
		var pre = document.createElement('div');
		pre.appendChild(document.createTextNode(highlightPreTag));
		
		highlightPostTag = highlightPostTag || '</em>';
		var post = document.createElement('div');
		post.appendChild(document.createTextNode(highlightPostTag));
		
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		
		return div.innerHTML
			.replace(RegExp(escapeRegExp(pre.innerHTML), 'g'), highlightPreTag)
			.replace(RegExp(escapeRegExp(post.innerHTML), 'g'), highlightPostTag)
	}
	
	function escapeRegExp(str) {
		return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
	}
	
	function compareVersions(left, right) {
		left = sanitizeVersion(left);
		right = sanitizeVersion(right);
		
		for (var i = 0; i < Math.max(left.length, right.length); i++) {
			if (left[i] > right[i]) {
				return -1;
			}
			if (left[i] < right[i]) {
				return 1;
			}
		}
		
		return 0;
	}
	
	function sanitizeVersion(input) {
		return input.split('.').map(function (n) {
			return parseInt(n, 10);
		});
	}
});
