// A simple example of a tree transformation plugin
// 
// This PHP-compiler plugin joins together several consecutive echos into a single one
// and if any of the parameters to an echo is a concatenation of strings
// it splits them and outputs them separately.
// This is much faster than first concatenating strings and then outputing the whole thing
// 
// For example, this:
/*
<?php
	echo "1" . (1 . $a ), 'ab';
	echo 'c' ,2;
	if ($a) {
		echo 2;
		//$a=1;
		echo $a;
	}
?>
*/
// is turned into this:
/*
<?php
	echo "1", 1, $a, "abc", 2;
	if($a)
	{
		echo 2, $a;
	}
?>
*/
// (it is actually the last step in a larger transformation in the works
// that creates a whole bunch of echos so I really need to compact them)
// 
// See comments in the source
// 
// By Daniel Barreiro: Satyam@satyam.com.ar
// with lots of help from the authors of PHP Compiler:  phpcompiler.org
// 
// License:  whichever applies to the PHP Compiler
// 


#include "compact_echo.h"

// Entry point for plugins
extern "C" void process_ast(AST_php_script* php_script)
{
	Compact_echo* ce = new Compact_echo();
	php_script->transform(ce);
}


AST_statement_list* Compact_echo::pre_statement_list(AST_statement_list* in) {
	// output statement list
	AST_statement_list* out = new AST_statement_list();

	// place to append the parameters to echos found
	AST_actual_parameter_list* pl = new AST_actual_parameter_list();

	// pattern of echo calls to be searched for
	AST_method_invocation* mi = new AST_method_invocation("echo",WILDCARD);
	mi->target = new Token_class_name(new String("%STDLIB%"));
	AST_eval_expr* echo_pattern = new AST_eval_expr(mi);
	
	
	// Loop through the input statement list
	List<AST_statement*>::const_iterator st;
	for(st = in->begin(); st != in->end(); st++) {
		// if the statement is an echo, gather the parameters.
		if ((*st)->match(echo_pattern)) {
		
			// pick the parameter list of the new found echo
			AST_actual_parameter_list* apl;
			apl = mi->actual_parameters;
			
			// Loop through the list of parameters
			List<AST_actual_parameter*>::const_iterator p;
			for(p = apl->begin(); p != apl->end(); p++) {
				// find out if the parameter is a string concatenation expresion and break it up.
				// Notice that I cannot launch a tree_transform here because I cannot go deep
				// I can only break up string concatenations in the very first level of parameters.
				breakup_string_cats(*p,pl);
			}
			// Since the pattern is ruined on a match, it has to be redone
			// but it is ruined only when a match is found, there is no need to build it anew every time.
			mi = new AST_method_invocation("echo",WILDCARD);
			mi->target = new Token_class_name(new String("%STDLIB%"));
			echo_pattern = new AST_eval_expr(mi);
			// if it was an echo, then skip adding it to the output statement list
			// at least until something that is not an echo is found
			// We only care about its parameters
			continue;
		}
		// if it reaches this far the statement is not a call to echo
		// otherwise, the continue would have skipped over from here on.
			
		// if there is a list of parameters in pl, then they have to be echoed before the statement that broke the sequence of echoes.
		push_back_echo(pl,out);
		// empty the parameter accumulator
		pl = new AST_actual_parameter_list();
		// add whatever it was to the output
		out->push_back(*st);
	}
	// if there is anything left in pl by the end of the statement list, echo it
	push_back_echo(pl,out);
	return out;
}

void Compact_echo::push_back_echo(AST_actual_parameter_list* pl,AST_statement_list* out) {
	// if there is nothing accumulated in the parameter list pl, there is nothing to do.
	if (pl->size() == 0) return;
	
	// build the echo
	AST_method_invocation* new_echo = new AST_method_invocation();
	new_echo->target =  new Token_class_name(new String("%STDLIB%"));
	new_echo->method_name = new Token_method_name(new String("echo"));
	new_echo->actual_parameters = new AST_actual_parameter_list();
	
	// The previous operations might have put two string literals consecutive, we'll make it one
	
	// Pattern to look for parameters made of a single string literal
	Token_string* ts = new Token_string(WILDCARD,WILDCARD);
	AST_actual_parameter* pattern = new AST_actual_parameter(false,ts);
	
	// Place to accumultate consecutive string literals
	String* accum = new String();
	
	// We'll browse through the parameters
	List<AST_actual_parameter*>::const_iterator p;
	for(p = pl->begin(); p != pl->end(); p++) {
		if ((*p)->match(pattern)) {
			// Instead of pushing it into the output parameter list
			// concatenate it into accum
			accum->append(ts->value->c_str());
			// patterns are destroyed on a succesfull match, so we have to renew it
			ts = new Token_string(WILDCARD,WILDCARD);
			pattern = new AST_actual_parameter(false,ts);
		// If it is not a literal string
		} else {
			// If there is something accumulated in accum
			if(accum->size() >0) {
				// add it as a new parameter
				new_echo->actual_parameters->push_back(new AST_actual_parameter(false,new Token_string(accum,accum)));
				// truncate the accumulator
				accum = new String();
			}
			// Push the parameter that was not a string literal
			new_echo->actual_parameters->push_back(*p);
		}
	}
	
	// No more parameters, if there is any string literal still in the accumulator, time to push it.
	if(accum->size() >0) {
		new_echo->actual_parameters->push_back(new AST_actual_parameter(false,new Token_string(accum,accum)));
	}
			
	// push the new echo into the output list
	out->push_back(new AST_eval_expr(new_echo));
}

// Checks whether a parameter of an echo is a string concatenation.
// If so, break it into into separate parameters
void Compact_echo::breakup_string_cats(AST_actual_parameter* ap,AST_actual_parameter_list* pl) {
	
 	// pattern to match a parameter made of a binary operation that has any two arguments joined by the "." string contatenation operator.
	AST_bin_op* bin_op= new AST_bin_op(WILDCARD,WILDCARD,".");
	AST_actual_parameter* pattern = new AST_actual_parameter(false,bin_op);
	
	// If it matches, break up any further contatenations in either the left or right expression
	if (ap->match(pattern)) {
		breakup_string_cats(new AST_actual_parameter(false,bin_op->left),pl);
		breakup_string_cats(new AST_actual_parameter(false,bin_op->right),pl);
	} else {
		// if it wasn't a string concatenation, just push it to the output parameter list.
		// Since  this method is called recursively, eventually it has to get here.
		pl->push_back(ap);
	}
}
