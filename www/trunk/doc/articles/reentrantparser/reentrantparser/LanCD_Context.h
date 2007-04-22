#ifndef LANCD_CONTEXT
#define LANCD_CONTEXT

#include <iostream>
#include <sstream>

using namespace std;

class LanCD_Context
{
public:
	void* scanner;
	int result;
	int c;
	int d;
	istream* is;
	int esc_depth;

public:
	LanCD_Context(istream* is = &cin)
	{
		init_scanner();
		this->is = is;
		c = 1;
		d = 1;
	}

	virtual ~LanCD_Context()
	{
		destroy_scanner();
	}

// Defined in LanCD.l
protected:
	void init_scanner();	
	void destroy_scanner();
};

int LanCD_parse(LanCD_Context*);

#endif // LANCD_CONTEXT
