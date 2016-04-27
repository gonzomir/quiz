=== Quiz ===
Contributors: andy, strider72, markjaquith
Tags: comment, spam, anti-spam, discussion, captcha
Tested up to: 4.5.1
Requires at least: 3.4
Stable tag: 1.3.1

Reduces spam and troll comments by requiring commenters to answer a question.

== Description ==

Commenters must answer a question correctly before their comment will be accepted.  This cuts down on spambots, and can also be helpful with troll-control.

This plugin adds a question/answer to your comment form.   Authors can write a new Quiz question and answer for each post, fall back on a default question, or have no question at all.

Answers are checked by a caseless comparison. So spelling must be correct but uppercase letters do not matter. Short answers of one or two words are best and punctuation should be avoided.

Questions should be crafted to check that the commenter read the post. So a post about your dog might have the question "What is my dog's name?" and a gallery post might ask "What brand of beer appears most often?"

You can enter multiple correct answers, separated by commas.  For example, if you enter:

6, six, half dozen

Either "6" or "six" or "half dozen" will be accepted as correct.

Logged in users with the ability to publish posts (Authors, Editors and Administrators, normally), do not have to answer the question in order to post a comment.

== Installation ==

Upload the 'quiz' folder to the '/wp-content/plugins/' directory and activate it in Admin.  If upgrading from 1.0, be sure to deactivate/upgrade/reactivate, or defaults won't be set.

== Instructions ==

When you write a post, enter the question and answer in the "Comment Quiz" meta box.  If you leave it blank, the default question will be used.  If you enter "noquiz" as the question, that post will not display a quiz.

To manually place the quiz form within you comments section, edit the comments.php file and insert the following:  do_action('comment_quiz')

There is a Settings screen if you want to customize things, including setting the default question or customizing the way the quiz displays on the page.

Tips on how to improve questions can be found at http://striderweb.com/nerdaphernalia/features/wp-comment-quiz/

(Please Note: the old "quicktag" method no longer works.  If you want to use that method, you should download version 1.0.  In 1.1 and above you enter the question in the meta box.)

== Frequently Asked Questions ==

= Why does the question still appear if I am an Author, Editor, or Administrator? =

To let you see the question as it appears to other visitors, and to not unnecessarily interfere with caching systems.

== Changelog ==

= 1.3.2 =
    * by SR
    * Improved hook for Settings link on Plugins page

= 1.3.1 =
    * by SR 2015-04-13
    * Restored 'comment_form' to comment_form hook for compatibility with older themes. (the_quiz will still only run once.)
    * Removed call to deprecated function screen_icon()
    * Code cleanup -- removed some unused functions

= 1.3 =
    * by SR 2015-02-28
    * Now hooks quiz to 'comment_form_after_fields'. Removed JavaScript that (maybe) repositioned quiz.
    * Minimum WP required: 3.4
	* Improved Plugin Description
	* Added translation headers
	* Updated log with 1.3b1 changes
	* Corrected language folder name to match textdomain call
	* Added a "lang" folder to hold po/mo/pot files

= 1.3 beta 1 =
	* By MJ 2012-09-11
	* Code improvements including dumping some old PHP 4 stuff
	* remove "Cleanup" functions option. Replaced with comment_quiz_compare filter.
	* Kill the footer links
	* Added code for AJAX callback
	* Updated textdomain calls
	* HTML5 style script tags
	* Bump the supported WP versions

= 1.2 =
	* by MJ 2010-02-03
	* BUGFIX -- custom questions didn't display
	* XSS vulnerabilities patched
	* Upped WP requirement to 2.8
	* Process options saving in a pre-output hook and redirect to &updated=1 to use WP's built-in "Settings saved" notice
	* Code cleanup
	* Store options in the database in a normal unslashed format (previously, it was stored slashed)
	* Skip question analysis for registered users with the ability to publish posts

= 1.1.1 =
 	* by SR 2008-11-28
	* BUGFIX -- was blocking pingbacks and trackbacks
	* Plugin no longer loads comment process hooks if we're in the Admin section.  Was causing problems with WP 2.7 inline replies
	* Updated Admin screen button CSS
	* Changed the default question -- some spambots can do math

= 1.1.1 =
 	* beta 1 by SR 2008-08-25
	* BUGFIX -- rejected all comments if set to "noquiz"  (No place to answer the question, but the (non)answer was tested anyway!)
	* BUGFIX -- if "noquiz" was entered as Answer on edit screen, it didn't save.  Now works for both Question and Answer

= 1.1 =
 	* by SR 2008-08-12
	* Allows for multiple possible answers (separate with commas)
	* Nonces on meta box
	* Fix for change to output of get_plugin_data() in WP 2.7  (grrrr...)

= 1.1 beta 6 =
 	* by SR
	* Improved set_defaults -- now unset and merge, tracks version
	* Improved get_plugin_data -- more efficient
	* Uses constructor instead of init()

= 1.1 beta 5 =
 	* by SR
	* Settings Screen in Admin -- no more editing files
	* "Comment Quiz" meta box in Post/Page edit screen -- no more shortcodes. Data stored same as before, so preexisting quizzes still work	 
	* No more [short tag] system functions.  Requires WP 2.5+ for the meta boxes.
	* Added wp_kses cleanup to quiz questions
	* Added strip_tags to default Cleanup functions
	* Legacy the_quiz() support (deprecated)
	* Users can add quiz to their themes with do_action('show_comment_quiz') & don't have to mess with function_exists()
	* Direct Settings link from Plugins page
	* Automatically figures out if form was manually inserted in theme. If auto-inserted, tries to reposition form via JavaScript
	* If quiz form lacks %question% placeholder, uses default form.
	* Moved functions into a class for code reusability and avoiding function name conflicts

= 1.0 =
 	* by AS
	* 1.0 is spaghetti code!
	* Shortcodes are removed from post_content and saved into postmeta so that disabling the plugin won't cause all the answers to appear on the blog.
	* Editors that do not use the standard API will not work properly with this because we need certain filters to be used.

== Screenshots ==

1. The Quiz settings page