Parser for SQL querys
=====================

This folder contains the parser for the SQL-querys you can give to The DataTank.

INPUT: string that represents a SQL-query  
OUTPUT: a Filter Syntax Tree. (as described in universalfilters/README.md)


What can this parser parse and convert to the Universal Syntax Tree?
--------------------------------------------------------------------

It can parse and convert:
 - SELECT... with optional aliases for columns
 - FROM (but universalfilters supports no joins yet, so no ",")
 - WHERE e
 - GROUP BY field, field, field
 - LIKE
 - IN (...)

 - nested Selects

 - Math: "+", "-", "*", "/"
 - Comparision: "<", ">", "<=", ">=", "<>", "!=", "="
 - Boolean operations: "AND", "OR", "NOT"
 - Functions:
   * Unary: "UCASE(\_)", "LCASE(\_)", "LEN(\_)", "ROUND(\_)", "ISNULL(\_)"
   * Binary: "MATCH\_REGEX(\_,\_)"
   * Tertairy: "MID(\_,\_,\_)", "REPLACE\_REGEX(\_,\_,\_)" 
   * Aggregators: "AVG(\_)", "COUNT(\_)", "FIRST(\_)", "LAST(\_)", "MAX(\_)", "MIN(\_)"

 (Note: "ISNULL(\_)", "MATCH\_REGEX(\_,\_)" and "REPLACE\_REGEX(\_,\_,\_)" are not SQL functions)


How I parse SQL querys
----------------------

example: ``SELECT * FROM package.resource``


 1. SQLTokenizer => split query in tokens
 
        "SELECT", "*", "FROM", "package.resource"
    
 2. SQLParser => categorize the tokens and give the tokens to the grammar
 
        "SELECT"        => category SELECT,     value null
        "*"             => category '*',        value null
        "FROM"          => category FROM,       value null
        "package.table" => category identifier, value "package.resource"
    
 3. SQLgrammar => build the tree.

        ...result:
        ColumnSelectionFilter(
            new ColumnSelectionFilterColumn(
                new Identifier("*"), null));
        ...after...
        Identifier("package.resource");


SQLgrammar.lime
---------------

The SQLgrammar is writen in lime-php. That's a php library to describe and parse context free grammars. It uses a notation that looks like Bachus Naur Form, but than with php-statements which tell the parser what to do if it matches a certain part.