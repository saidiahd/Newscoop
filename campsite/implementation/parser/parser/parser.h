/******************************************************************************

CAMPSITE is a Unicode-enabled multilingual web content
management system for news publications.
CAMPFIRE is a Unicode-enabled java-based near WYSIWYG text editor.
Copyright (C)2000,2001  Media Development Loan Fund
contact: contact@campware.org - http://www.campware.org
Campware encourages further development. Please let us know.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

******************************************************************************/

/******************************************************************************

Define CParser class; this performs the semantic analisys and builds up the
actions tree. Every action corresponds to a template instruction. There are two
important methods: Parse - perform the parsing (builds the actions tree) and
WriteOutput - scans the actions tree and writes out the output. WriteOutput
receives a "context" as a parameter. The context contains all the cgi parameters
and other context information such as: user subscriptions, issue/section/article/
subtitle list index etc. By scanning the actions tree the context changes.

******************************************************************************/

#ifndef _CMS_PARSER
#define _CMS_PARSER

#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <list>

#include "cms_types.h"
#include "atoms.h"
#include "lex.h"
#include "context.h"
#include "actions.h"
#include "mutex.h"

class CErrorList : public list <CError*>
{
public:
	CErrorList() {}

	CErrorList(const CErrorList& o) { *this = o; }

	~CErrorList() { clear(); }

	void clear();

	const CErrorList& operator =(const CErrorList&);
};

// ExStat class; exception thrown by CParser class: unable to stat template file
class ExStat
{
public:
	ExStat(int p_nErrNr) : m_nErrNr(p_nErrNr) {}
	~ExStat() {}

	int ErrNr() const { return m_nErrNr; }

private:
	int m_nErrNr;
};

class CParserMap : public map<string, CParser*>
{
	friend class CParser;

public:
	// default constructor
	CParserMap() {}

	// virtual destructor
	virtual ~CParserMap() { Clear(); }

	CParser* find(const string&) const;

private:
	bool Insert(CParser*);

	void Erase(CParser*);

	void Reset() const;

	void Clear();

	// deactivate copy-constructor
	CParserMap(const CParserMap&);

	// deactivate assign operator
	const CParserMap& operator =(const CParserMap&);
};

// CParser: the template parser. Contains a lex, the semantic analyser and actions tree.
// The actions tree is generated by parsing the template.
// Important methods:
//		Parse - start the parser; build actions tree
//		WriteOutput - write the actions output; Parse must be called first
class CParser
{
private:
	CLex lex;					// lex
	string document_root;		// document root of current template
	string tpl;					// template file (complete path)
	struct stat tpl_stat;		// template file stat information
	CActionList actions;		// actions tree
	CErrorList parse_err;		// list of parse errors
	CErrorList write_err;		// list of write errors
	bool parsed;				// true if the template was parsed
	TK_bool parse_err_printed;	// thread key variable: boolean value: true if parse
								// errors were printed
	TK_bool write_err_printed;	// thread key variable: boolean value: true if write
								// errors were printed
	StringSet parent_tpl;		// parent templates including this template (empty if none)
	StringSet child_tpl;		// child templates included by this template (empty if none)
	String2String fields;		//
	ULInt m_nTplFileLen;		// template file length
	int m_nTplFD;				// template file descriptor
	const char* m_pchTplBuf;	// buffer mapped to template file
	CRWMutex m_coOpMutex;		// mutex used to control parser operations
	
	// static members
	static CCParser cparser;			// article content parser
	static CParserMap m_coPMap;			// map of parsers: a parser instance
										// corresponds to a certain template
	static CMutex m_coMapMutex;			// mutex used to control parser map operations
	static TK_MYSQL s_MYSQL;			// thread key variable: pointer to MySQL connection

	mutable const CLexem* l;			// last lexem read
	mutable const CAttribute* attr;		// last attribute read
	mutable const CStatement* st;		// last statement read

private:	// private methods
	// copy-constructor
	CParser(const CParser&);

	// assign operator
	const CParser& operator =(const CParser&);

	// MapTpl: map template file to m_pchTplBuf buffer
	void MapTpl() throw (ExStat);

	// UnMapTpl: unmap template file
	void UnMapTpl() throw();

	// LvStatements: return string containig statement names allowed in a given level:
	// root, list issue, list section, list article, list subtitle
	// Parameters:
	//		int level - level: LV_ROOT, LV_LISSUE, LV_LSECTION, LV_LARTICLE, LV_LSUBTITLE
	const char* LvStatements(int level);

	// LvListSt: return string containig list type statements allowed in a given level:
	// root, list issue, list section, list article, list subtitle
	// Parameters:
	//		int level - level: LV_ROOT, LV_LISSUE, LV_LSECTION, LV_LARTICLE, LV_LSUBTITLE
	const char* LvListSt(int level);

	// IfStatements: return string containing if type statements allowed in a given level
	// (root, list issue, list section, list article, list subtitle)
	// and sublevel (user, login, search, with etc.)
	// Parameters:
	//		int level - level: LV_ROOT, LV_LISSUE, LV_LSECTION, LV_LARTICLE, LV_LSUBTITLE
	//		int sublevel - sublevel: SUBLV_NONE, SUBLV_IFPREV, SUBLV_IFNEXT, SUBLV_IFLIST,
	//			SUBLV_IFISSUE, SUBLV_IFSECTION, SUBLV_IFARTICLE, SUBLV_EMPTYLIST,
	//			SUBLV_IFALLOWED, SUBLV_SUBSCRIPTION, SUBLV_USER, SUBLV_LOGIN, SUBLV_LOCAL
	//			SUBLV_IFPUBLICATION, SUBLV_SEARCH, SUBLV_SEARCHRESULT, SUBLV_WITH
	string IfStatements(int level, int sublevel);

	// PrintStatements: return string containig print type statements allowed in a given level
	// (root, list issue, list section, list article, list subtitle)
	// and sublevel (user, login, search, with etc.)
	string PrintStatements(int level, int sublevel);

	// EditStatements: return string containig edit type statements allowed in a given
	// sublevel (user, login, search, with etc.)
	string EditStatements(int sublevel);

	// SelectStatements: return string containig select type statements allowed in a given
	// sublevel (user, login, search, with etc.)
	string SelectStatements(int sublevel);

	// DEBUGLexem: print lexem debug information
	void DEBUGLexem(const char* c, const CLexem* l);

	// WaitForStatementStart: read from input file until it finds a start statement
	const CLexem* WaitForStatementStart(CActionList& al);

	// WaitForStatementEnd: read from input file until it finds an end statement
	const CLexem* WaitForStatementEnd(bool write_errors);

	// LMod2Level: return level corresponding to given list modifier
	int LMod2Level(int);

	// ValidDateForm: return true if given string is a valid date format
	int ValidDateForm(const char* df);

	// IsTopicStatement: returns true if the given lexem is the topic statement
	bool IsTopicStatement(const CLexem* p_pcoLexem) const;

	// SetWriteErrors: set the parse_err_printed and write_err_printed members
	// for this parser instance and for included templates
	void SetWriteErrors(bool p_bWriteErrors);

	// HLanguage: parse language statement; add CActLanguage action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HLanguage(CActionList& al, int lv, int sublv);
	
	// HInclude: parse include statement; add CActInclude action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	int HInclude(CActionList& al);
	
	// HPublication: parse publication statement; add CActPublication action to actions
	// list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int HPublication(CActionList& al, int level, int sublevel);
	
	// HIssue: parse issue statement; add CActIssue action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int HIssue(CActionList& al, int level, int sublevel);
	
	// HSection: parse section statement; add CActSection action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int HSection(CActionList& al, int level, int sublevel);
	
	// HArticle: parse article statement; add CActArticle action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int HArticle(CActionList& al, int level, int sublevel);
	
	// HTopic: parse topic statement; add CActTopic action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int HTopic(CActionList& al, int level, int sublevel);
	
	// HURLParameters: parse URLParameters statement; add CActURLParameters action to
	// actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	int HURLParameters(CActionList& al);
	
	// HFormParameters: parse FormParameters statement; add CActFormParameters action to
	// actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	int HFormParameters(CActionList& al);
	
	// HDate: parse date statement; add CActDate action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	int HDate(CActionList& al);
	
	// HPrint: parse print statement; add CActPrint action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HPrint(CActionList& al, int lv, int sublv);
	
	// HList: parse list statement; add CActList action to actions list (al)
	// All statements between List and EndList statements are parsed, added as actions
	// in CActList's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int HList(CActionList& al, int level, int sublevel);
	
	// HIf: parse if statement; add CActIf action to actions list (al)
	// All statements between If and EndIf statements are parsed, added as actions
	// in CActIf's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HIf(CActionList& al, int lv, int sublv);
	
	// HLocal: parse local statement; add CActLocal action to actions list (al)
	// All statements between Local and EndLocal statements are parsed, added as actions
	// in CActLocal's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HLocal(CActionList& al, int lv, int sublv);
	
	// HSubscription: parse subscription statement; add CActSubscription action to actions
	// list (al)
	// All statements between Subscription and EndSubscription statements are parsed,
	// added as actions in CActSubscription's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HSubscription(CActionList& al, int lv, int sublv);
	
	// HEdit: parse edit statement; add CActEdit action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HEdit(CActionList& al, int lv, int sublv);
	
	// HSelect: parse select statement; add CActSelect action to actions list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HSelect(CActionList& al, int lv, int sublv);
	
	// HUser: parse user statement; add CActUser action to actions list (al)
	// All statements between User and EndUser statements are parsed, added as actions
	// in CActUsers's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HUser(CActionList& al, int lv, int sublv);
	
	// HLogin: parse login statement; add CActLogin action to actions list (al)
	// All statements between Login and EndLogin statements are parsed, added as actions
	// in CActLogin's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HLogin(CActionList& al, int lv, int sublv);
	
	// HSearch: parse search statement; add CActSearch action to actions list (al)
	// All statements between Search and EndSearch statements are parsed, added as actions
	// in CActSearch's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HSearch(CActionList& al, int lv, int sublv);
	
	// HWith: parse with statement; add CActWith action to actions list (al)
	// All statements between With and EndWith statements are parsed, added as actions
	// in CActWith's list of actions
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int lv - current level
	//		int sublv - current sublevel
	int HWith(CActionList& al, int lv, int sublv);
	
	// LevelParser: read lexems until it finds a statement or reaches end of file
	// Depending on read statement it calls on of HArticle, HDate, HEdit, HFormParameters,
	// HIf, HInclude, HIssue, HLanguage, HList, HLocal, HLogin, HPrint, HPublication,
	// HSearch, HSection, HSelect, HSubscription, HURLParameters, HUser, HWith methods.
	// It does not add actions to action list (al)
	// Parameters:
	//		CActionList& al - reference to actions list
	//		int level - current level
	//		int sublevel - current sublevel
	int LevelParser(CActionList& al, int level, int sublevel);

public:
	// CParser: constructor
	// Parameters:
	//		const string& p_rcoTpl - template path
	//		const string& dr - document root
	CParser(const string& p_rcoTpl, const string& dr = string(""));

	// destructor
	~CParser();

	// getTpl: return template file name
	const string& getTpl() const { return tpl; }

	// setMYSQL: set MySQL connection
	// Parameters:
	//		MYSQL* p_pMYSQL - pointer to MySQL server connection
	static void setMYSQL(MYSQL*);

	// reset: reset parser: clear actions tree, reset lex, clear errors list
	void reset();

	// resetMap: reset all the parsers in the hash
	static void resetMap();

	// map: return parsers map
	static const CParserMap& map() { return m_coPMap; }

	// parserOf: return pointer to parser of template
	static CParser* parserOf(const string& p_rcoTpl, const string& p_rcoDocRoot = string(""));

	// setDebug: set debug on/off
	void setDebug(bool d) { CAction::setDebug(d); }

	// debug: return debug value: true/false
	bool debug() const { return CAction::debug(); }

	// parse: start the parser; return the parse result
	// Parameters:
	//		bool force = false - if true, force reparsing of template; if false, do not
	//			parse the template again if already parsed
	int parse(bool force = false);

	// writeOutput: write actions output to given file stream
	// Parameters:
	//		const CContext& c - context
	//		sockstream& fs - output file stream
	int writeOutput(const CContext& c, sockstream& fs);

	// printParseErrors: print parse errors to given output stream
	// Parameters:
	//		sockstream& fs - output file stream
	//		bool p_bMainTpl = false - true if this is the main template
	void printParseErrors(sockstream& fs, bool p_bMainTpl = false);

	// printWriteErrors: print write errors to given output stream
	// Parameters:
	//		sockstream& fs - output file stream
	//		bool p_bMainTpl = false - true if this is the main template
	void printWriteErrors(sockstream& fs, bool p_bMainTpl = false);

	// testLex: test the lex; debug purposes only
	void testLex();

	// PrintLexStatements: print lex statements; debug purposes only
	void printLexStatements() { lex.printStatements(); }
};

#endif
