< ? p h p 
 
 
 
 / * = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 C o p y r i g h t   P r o j e c t   B e e h i v e F o r u m   2 0 0 2 
 
 
 
 T h i s   f i l e   i s   p a r t   o f   B e e h i v e F o r u m . 
 
 
 
 B e e h i v e F o r u m   i s   f r e e   s o f t w a r e ;   y o u   c a n   r e d i s t r i b u t e   i t   a n d / o r   m o d i f y 
 
 i t   u n d e r   t h e   t e r m s   o f   t h e   G N U   G e n e r a l   P u b l i c   L i c e n s e   a s   p u b l i s h e d   b y 
 
 t h e   F r e e   S o f t w a r e   F o u n d a t i o n ;   e i t h e r   v e r s i o n   2   o f   t h e   L i c e n s e ,   o r 
 
 ( a t   y o u r   o p t i o n )   a n y   l a t e r   v e r s i o n . 
 
 
 
 B e e h i v e F o r u m   i s   d i s t r i b u t e d   i n   t h e   h o p e   t h a t   i t   w i l l   b e   u s e f u l , 
 
 b u t   W I T H O U T   A N Y   W A R R A N T Y ;   w i t h o u t   e v e n   t h e   i m p l i e d   w a r r a n t y   o f 
 
 M E R C H A N T A B I L I T Y   o r   F I T N E S S   F O R   A   P A R T I C U L A R   P U R P O S E .     S e e   t h e 
 
 G N U   G e n e r a l   P u b l i c   L i c e n s e   f o r   m o r e   d e t a i l s . 
 
 
 
 Y o u   s h o u l d   h a v e   r e c e i v e d   a   c o p y   o f   t h e   G N U   G e n e r a l   P u b l i c   L i c e n s e 
 
 a l o n g   w i t h   B e e h i v e ;   i f   n o t ,   w r i t e   t o   t h e   F r e e   S o f t w a r e 
 
 F o u n d a t i o n ,   I n c . ,   5 9   T e m p l e   P l a c e ,   S u i t e   3 3 0 ,   B o s t o n ,   M A     0 2 1 1 1 - 1 3 0 7 
 
 U S A 
 
 = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = * / 
 
 
 
 / *   $ I d :   b h _ u p d a t e _ l a n g u a g e . p h p , v   1 . 3   2 0 0 7 / 0 2 / 2 2   2 1 : 3 8 : 0 2   d e c o y d u c k   E x p   $   * / 
 
 
 
 / /   C o n s t a n t   t o   d e f i n e   w h e r e   t h e   i n c l u d e   f i l e s   a r e   / / 
 
 
 
 d e f i n e ( " B H _ I N C L U D E _ P A T H " ,   " . / f o r u m / i n c l u d e / " ) ; 
 
 
 
 / /   F u n c t i o n   t o   l o a d   B H   l a n g u a g e   f i l e   i n t o   $ l a n g   a r r a y   / / 
 
 
 
 f u n c t i o n   l o a d _ l a n g u a g e _ f i l e ( $ f i l e n a m e ) 
 
 { 
 
         i f   ( f i l e _ e x i s t s ( B H _ I N C L U D E _ P A T H .   " l a n g u a g e s / $ f i l e n a m e " ) )   { 
 
         
 
                 i n c l u d e ( B H _ I N C L U D E _ P A T H .   " l a n g u a g e s / $ f i l e n a m e " ) ; 
 
                 r e t u r n   $ l a n g ; 
 
         } 
 
 
 
         r e t u r n   f a l s e ; 
 
 } 
 
 
 
 / /   C h e c k s   f o r   M a g i c   Q u o t e s   a n d   p e r f o r m   s t r i p s l a s h e s   i f   n e s s e c a r y   / / 
 
 
 
 f u n c t i o n   _ s t r i p s l a s h e s ( $ s t r i n g ) 
 
 { 
 
         i f   ( g e t _ m a g i c _ q u o t e s _ g p c ( )   = =   1 )   { 
 
                 r e t u r n   s t r i p s l a s h e s ( $ s t r i n g ) ; 
 
         } e l s e   { 
 
                 r e t u r n   $ s t r i n g ; 
 
         } 
 
 } 
 
 
 
 / /   S t a r t   h e r e   / / 
 
 
 
 $ v a l i d   =   t r u e ; 
 
 
 
 i f   ( i s s e t ( $ _ S E R V E R [ ' a r g v ' ] [ 1 ] )   & &   s t r l e n ( t r i m ( _ s t r i p s l a s h e s ( $ _ S E R V E R [ ' a r g v ' ] [ 1 ] ) ) ) )   { 
 
 
 
         $ t a r g e t _ l a n g u a g e _ f i l e   =   t r i m ( _ s t r i p s l a s h e s ( $ _ S E R V E R [ ' a r g v ' ] [ 1 ] ) ) ; 
 
 
 
         i f   ( ! $ l a n g _ f i x   =   l o a d _ l a n g u a g e _ f i l e ( $ t a r g e t _ l a n g u a g e _ f i l e ) )   { 
 
                 
 
                 e c h o   " F a i l e d   t o   l o a d   l a n g u a g e   f i l e .   C h e c k   w o r k i n g   d i r e c t o r y   a n d   f i l e n a m e . \ n " ; 
 
                 e x i t ; 
 
         } 
 
 
 
 } e l s e   { 
 
 
 
         e c h o   " N o   t a r g e t   l a n g u a g e   f i l e   s p e c i f i e d . \ n " ; 
 
         e x i t ; 
 
 } 
 
 
 
 i f   ( i s s e t ( $ _ S E R V E R [ ' a r g v ' ] [ 2 ] )   & &   s t r l e n ( t r i m ( _ s t r i p s l a s h e s ( $ _ S E R V E R [ ' a r g v ' ] [ 2 ] ) ) ) )   { 
 
 
 
         $ a d d i t i o n s _ f i l e   =   t r i m ( _ s t r i p s l a s h e s ( $ _ S E R V E R [ ' a r g v ' ] [ 2 ] ) ) ; 
 
 
 
         i f   ( ! $ l a n g _ a d d   =   l o a d _ l a n g u a g e _ f i l e ( $ a d d i t i o n s _ f i l e ) )   { 
 
 
 
                 e c h o   " F a i l e d   t o   l o a d   a d d i t i o n s   f i l e .   C h e c k   w o r k i n g   d i r e c t o r y   a n d   f i l e n a m e . \ n " ; 
 
                 e x i t ; 
 
         } 
 
 
 
 } e l s e   { 
 
 
 
         e c h o   " N o   a d d i t i o n s   f i l e   s p e c i f i e d . \ n " ; 
 
         e x i t ; 
 
 } 
 
 
 
 i f   ( f i l e _ e x i s t s ( B H _ I N C L U D E _ P A T H .   " l a n g u a g e s / e n . i n c . p h p " ) )   { 
 
 
 
         $ l a n g _ e n   =   f i l e ( B H _ I N C L U D E _ P A T H .   " l a n g u a g e s / e n . i n c . p h p " ) ; 
 
 
 
         f o r e a c h   ( $ l a n g _ e n   a s   $ l i n e _ n u m   = >   $ l a n g _ e n _ l i n e )   { 
 
 
 
                 $ l a n g _ e n _ l i n e   =   t r i m ( $ l a n g _ e n _ l i n e ) ; 
 
                 
 
                 i f   ( p r e g _ m a t c h ( " / ^ \ \ \ $ l a n g ( ( \ [ [ ^ \ ] ] + \ ] ) + ) / " ,   $ l a n g _ e n _ l i n e ,   $ l a n g _ m a t c h e s ) )   { 
 
                         
 
                         $ p h p _ c o d e   =   " i f   ( i s s e t ( \ $ l a n g _ a d d { $ l a n g _ m a t c h e s [ 1 ] } ) )   { " ; 
 
                         $ p h p _ c o d e . =   " e c h o   \ " \ \ \ $ l a n g { $ l a n g _ m a t c h e s [ 1 ] }   =   \ \ \ " \ " ,   " ; 
 
                         $ p h p _ c o d e . =   " a d d s l a s h e s ( \ $ l a n g _ a d d { $ l a n g _ m a t c h e s [ 1 ] } ) ,   \ " \ \ \ " ; \ n \ " ; " ; 
 
                         $ p h p _ c o d e . =   " } e l s e i f   ( i s s e t ( \ $ l a n g _ f i x { $ l a n g _ m a t c h e s [ 1 ] } ) )   { " ; 
 
                         $ p h p _ c o d e . =   " e c h o   \ " \ \ \ $ l a n g { $ l a n g _ m a t c h e s [ 1 ] }   =   \ \ \ " \ " ,   " ; 
 
                         $ p h p _ c o d e . =   " a d d s l a s h e s ( \ $ l a n g _ f i x { $ l a n g _ m a t c h e s [ 1 ] } ) ,   \ " \ \ \ " ; \ n \ " ; " ; 
 
                         $ p h p _ c o d e . =   " } " ; 
 
 
 
                         e v a l ( $ p h p _ c o d e ) ; 
 
 
 
                 } e l s e   { 
 
 
 
                         e c h o   " $ l a n g _ e n _ l i n e \ n " ; 
 
                 } 
 
         } 
 
 
 
 } e l s e   { 
 
 
 
         e c h o   " F a i l e d   t o   l o a d   E n g l i s h   l a n g u a g e   f i l e .   C h e c k   w o r k i n g   d i r e c t o r y   a n d   f i l e n a m e . \ n " ; 
 
 } 
 
 
 
 ? > 