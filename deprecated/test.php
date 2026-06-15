<?php

		/*add_rewrite_rule(
			'^' . $pagename . '/([0-9]+/)?$', 
			'index.php?pagename=' . $pagename . '&' . $query . '=$matches[1]',
			'top'
		); 

		/*add_rewrite_rule(
			'^' . $pagename . '/([0-9]+/)?$',
			//'/^' . $pagename . '\/([0-9]+\/)?(\?(.+))?$/',
			//'index.php?pagename=' . $pagename . '&' . $query . '=$matches[1]&$matches[3]',
			'index.php?pagename=' . $pagename . '&' . $query . '=$matches[1]',
			'top'
		);*/

		/*add_rewrite_rule(
			//'^' . $pagename .'/([0-9]+/)(lang=([-a-z\-])?$',
			//'^' . $pagename .'/([0-9]+)/?$',
			'/^' . $pagename . '\/([0-9]+\/)?(\?.+)?$/',
			'index.php?pagename=' . $pagename . '&' . $query . '=$matches[1]' . $lang,
			'top'
		);*/