/* This is autogenerated code. */

/* (generated Sat, 31 Mar 2012 13:08:19 +0200 by ladder-gen v 1.0) */

#ifndef LADDER_H
#define LADDER_H

#if ARDUINO >= 100
    #include "Arduino.h"   
#else
    #include "WProgram.h"
#endif

#define BOOL boolean
#define SWORD int

#define EXTERN_EVERYTHING
#define NO_PROTOTYPES

void PlcCycle(void);

/* Configure digital I/O according to LD (call this in setup()). */

inline void PlcSetup()
{
    pinMode(3, INPUT);
    pinMode(8, OUTPUT);
    pinMode(2, INPUT);
    pinMode(9, OUTPUT);
}


/* Individual pins (this code is used in ladder.cpp) */

inline extern BOOL Read_U_b_Xgreen_btn(void)
{
    return digitalRead(3);
}

inline BOOL Read_U_b_Ygreen_led(void)
{
    return digitalRead(8);
}

inline void Write_U_b_Ygreen_led(BOOL v)
{
    digitalWrite(8, v);
}

inline extern BOOL Read_U_b_Xred_btn(void)
{
    return digitalRead(2);
}

inline BOOL Read_U_b_Yred_led(void)
{
    return digitalRead(9);
}

inline void Write_U_b_Yred_led(BOOL v)
{
    digitalWrite(9, v);
}


#endif
