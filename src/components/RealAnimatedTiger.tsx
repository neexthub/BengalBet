import React, { useState } from 'react';
import tigerFaceImg from '../assets/images/real_tiger_face_1790007904695.jpg';

interface RealAnimatedTigerProps {
  size?: 'xs' | 'sm' | 'md' | 'lg';
  className?: string;
  onClick?: () => void;
}

export const RealAnimatedTiger: React.FC<RealAnimatedTigerProps> = ({
  size = 'sm',
  className = '',
  onClick
}) => {
  const [isRoaring, setIsRoaring] = useState(false);

  const sizeClasses = {
    xs: 'w-7 h-7 sm:w-8 sm:h-8',
    sm: 'w-8 h-8 xs:w-8.5 xs:h-8.5 sm:w-9.5 sm:h-9.5',
    md: 'w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12',
    lg: 'w-14 h-14 sm:w-16 sm:h-16'
  }[size];

  const handleInteraction = () => {
    setIsRoaring(true);
    setTimeout(() => setIsRoaring(false), 900);
    if (onClick) onClick();
  };

  return (
    <div
      id="real-animated-tiger-container"
      onClick={handleInteraction}
      className={`relative select-none cursor-pointer group flex items-center justify-center shrink-0 ${sizeClasses} ${className}`}
      title="Royal Bengal Tiger"
      role="button"
      aria-label="Bengal Tiger Mascot"
    >
      {/* Dynamic atmospheric ambient aura around the tiger - no circle border */}
      <div 
        className={`absolute -inset-1 transition-all duration-300 pointer-events-none blur-md ${
          isRoaring 
            ? 'bg-amber-400/50 opacity-100 scale-125 animate-pulse' 
            : 'bg-amber-500/25 opacity-40 group-hover:opacity-100 group-hover:scale-110'
        }`}
      />

      {/* Realistic Tiger Head Housing - Seamless Finish, No Circle Box or Border */}
      <div 
        className={`relative w-full h-full flex items-center justify-center transition-all duration-300 ${
          isRoaring ? 'animate-bounce scale-110' : 'group-hover:scale-105'
        }`}
        style={{
          filter: 'drop-shadow(0 2px 7px rgba(255, 170, 0, 0.5)) drop-shadow(0 1px 3px rgba(0, 0, 0, 0.9))'
        }}
      >
        {/* Real Moving Tiger Layer - Breathing, Panning & Head Swaying with Seamless Edge Fade */}
        <div 
          className="w-full h-full relative"
          style={{
            animation: 'realTigerLiving 6s ease-in-out infinite',
            WebkitMaskImage: 'radial-gradient(ellipse 96% 92% at 50% 50%, black 58%, rgba(0,0,0,0.85) 75%, transparent 100%)',
            maskImage: 'radial-gradient(ellipse 96% 92% at 50% 50%, black 58%, rgba(0,0,0,0.85) 75%, transparent 100%)'
          }}
        >
          <img
            src={tigerFaceImg}
            alt="Real Royal Bengal Tiger"
            referrerPolicy="no-referrer"
            className="w-full h-full object-cover object-center scale-110 group-hover:scale-115 transition-transform duration-500"
          />

          {/* Realistic Dynamic Fur Light Shimmer / Sunlight Ripple */}
          <div 
            className="absolute inset-0 bg-gradient-to-tr from-transparent via-amber-200/25 to-transparent pointer-events-none mix-blend-overlay"
            style={{
              animation: 'realTigerShimmer 4s ease-in-out infinite'
            }}
          />

          {/* Glowing Realistic Tiger Eyes with Blinking Effect */}
          {/* Left Eye */}
          <div 
            className="absolute top-[38%] left-[34%] w-[8.5%] h-[8.5%] rounded-full bg-amber-300 shadow-[0_0_8px_#f59e0b] pointer-events-none mix-blend-screen"
            style={{
              animation: 'realTigerBlink 5.2s ease-in-out infinite'
            }}
          />
          {/* Right Eye */}
          <div 
            className="absolute top-[38%] right-[34%] w-[8.5%] h-[8.5%] rounded-full bg-amber-300 shadow-[0_0_8px_#f59e0b] pointer-events-none mix-blend-screen"
            style={{
              animation: 'realTigerBlink 5.2s ease-in-out infinite'
            }}
          />
        </div>

        {/* Soft Vignette Depth Accent */}
        <div className="absolute inset-0 pointer-events-none mix-blend-multiply opacity-30" />
      </div>

      {/* Ripple shockwave when clicked / roaring */}
      {isRoaring && (
        <span className="absolute inset-0 scale-125 border-2 border-amber-300 rounded-lg animate-ping pointer-events-none opacity-80" />
      )}
    </div>
  );
};
