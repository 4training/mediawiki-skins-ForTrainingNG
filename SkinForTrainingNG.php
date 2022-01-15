<?php
namespace ForTrainingNG;
use SkinMustache;
use Sanitizer;
use Linker;
use Html;

/*
 * Some customizations for our Skin: All "real" code changes are marked with custom4training
 * The rest is just copied from SkinMustache or Skin
 */
class SkinForTrainingNG extends SkinMustache {

	/** @inheritDoc */
	public function __construct( $options ) {
		global $wgVersion;
		if ( version_compare( $wgVersion, '1.38', '<' ) ) {
			$options['templateDirectory'] = 'skins/ForTrainingNG/templates';
		}
		parent::__construct( $options );
	}

	/*
	 * custom4training: Overriding this function to do some customizations
	 */
	protected function getPortletData( $name, array $items ) {
		if (($name === 'tb') && !$this->getSkin()->getUser()->isRegistered()) {
			// Show toolbar only for logged-in users
			return null;
		} else {
			return $this->getPortletDataCustom( $name, $items);
		}
	}

	// This is just copied from the SkinMustache class
	protected function getPortletDataCustom( $name, array $items ) {
		// Monobook and Vector historically render this portal as an element with ID p-cactions
		// This inconsistency is regretful from a code point of view
		// However this ensures compatibility with gadgets.
		// In future we should port p-#cactions to #p-actions and drop this rename.
		if ( $name === 'actions' ) {
			$name = 'cactions';
		}

		// user-menu is the new personal tools, without the notifications.
		// A lot of user code and gadgets relies on it being named personal.
		// This allows it to function as a drop-in replacement.
		if ( $name === 'user-menu' ) {
			$name = 'personal';
		}

		$id = Sanitizer::escapeIdForAttribute( "p-$name" );
		$data = [
			'id' => $id,
			'class' => 'mw-portlet ' . Sanitizer::escapeClass( "mw-portlet-$name" ),
			'html-tooltip' => Linker::tooltip( $id ),
			'html-items' => '',
			// Will be populated by SkinAfterPortlet hook.
			'html-after-portal' => '',
		];
		// Run the SkinAfterPortlet
		// hook and if content is added appends it to the html-after-portal
		// for output.
		// Currently in production this supports the wikibase 'edit' link.
		$content = $this->getAfterPortlet( $name );
		if ( $content !== '' ) {
			$data['html-after-portal'] = Html::rawElement(
				'div',
				[
					'class' => [
						'after-portlet',
						Sanitizer::escapeClass( "after-portlet-$name" ),
					],
				],
				$content
			);
		}

		foreach ( $items as $key => $item ) {
			$data['html-items'] .= $this->makeListItemCustom( $key, $item );
		}

		$data['label'] = $this->portletLabel( $name );
		$data['class'] .= ( count( $items ) === 0 && $content === '' )
			? ' emptyPortlet' : '';
		return $data;
	}

	// This is just copied from the SkinMustache class
	protected function portletLabel( $name ) {
		// For historic reasons for some menu items,
		// there is no language key corresponding with its menu key.
		$mappings = [
			'tb' => 'toolbox',
			'personal' => 'personaltools',
			'lang' => 'otherlanguages',
		];
		$msgObj = $this->msg( $mappings[ $name ] ?? $name );
		// If no message exists fallback to plain text (T252727)
		$labelText = $msgObj->exists() ? $msgObj->text() : $name;
		return $labelText;
	}

	/*
	 * This is taken from the Skin class
	 * The makeListItem() function is not supposed to be overriden but we want to change some behaviour
	 * That's why we copied a lot of code to be able to do that
	 * Main change: instead of <li><a href="...">menu item</a></li> we want to generate just
	 * <a href="...">menu item</a>
	 */
	protected function makeListItemCustom( $key, $item, $options = [] ) {
		// In case this is still set from SkinTemplate, we don't want it to appear in
		// the HTML output (normally removed in SkinTemplate::buildContentActionUrls())
		unset( $item['redundant'] );

		if ( isset( $item['links'] ) ) {
			$links = [];
			foreach ( $item['links'] as $linkKey => $link ) {
				$links[] = $this->makeLink( $linkKey, $link, $options );
			}
			$html = implode( ' ', $links );
		} else {
			$link = $item;
			// These keys are used by makeListItem and shouldn't be passed on to the link
			foreach ( [ 'id', 'class', 'active', 'tag', 'itemtitle' ] as $k ) {
				unset( $link[$k] );
			}
			if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
				// The id goes on the <li> not on the <a> for single links
				// but makeSidebarLink still needs to know what id to use when
				// generating tooltips and accesskeys.
				$link['single-id'] = $item['id'];
			}
			if ( isset( $link['link-class'] ) ) {
				// link-class should be set on the <a> itself,
				// so pass it in as 'class'
				$link['class'] = $link['link-class'];
				unset( $link['link-class'] );
			}
			$html = $this->makeLink( $key, $link, $options );
		}

		$attrs = [];
		foreach ( [ 'id', 'class' ] as $attr ) {
			if ( isset( $item[$attr] ) ) {
				$attrs[$attr] = $item[$attr];
			}
		}
		if ( isset( $item['active'] ) && $item['active'] ) {
			if ( !isset( $attrs['class'] ) ) {
				$attrs['class'] = '';
			}

			// In the future, this should accept an array of classes, not a string
			if ( is_array( $attrs['class'] ) ) {
				$attrs['class'][] = 'active';
			} else {
				$attrs['class'] .= ' active';
				$attrs['class'] = trim( $attrs['class'] );
			}
		}
		if ( isset( $item['itemtitle'] ) ) {
			$attrs['title'] = $item['itemtitle'];
		}

		// custom4training: Workaround to remove the first item of navigation sub-menu
		// TODO: Remove this once the CustomSidebar extension is improved
		if ($key == 0) {
			return "";
		}

		// custom4training: No <li> around the links
		$attrs = array("class" => "block text-lg p-2 hover:bg-gray-200", "href" => $link["href"] ?? '#' );
		if ( !isset( $link['text'] ) ) {
			$text = $item['text'] ?? $this->msg( $item['msg'] ?? $key )->text();
			return Html::rawElement('a', $attrs, htmlspecialchars( $text ) );
		} else {
			return Html::rawElement('a', $attrs, $link["text"] );
		}
		// return Html::rawElement( $options['tag'] ?? 'li', $attrs, $html );
	}


	final public function console_log( $data ){
		echo '<script>';
		echo 'console.log('. json_encode( $data ) .')';
		echo '</script>';
	}
}