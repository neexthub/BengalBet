import React, { useState, useEffect } from 'react';
import { Heart } from 'lucide-react';
import { GameItem } from '../types';
import { GAMES_DATA } from '../data/games';
import { useFavorites } from '../hooks/useFavorites';
import boxingKingIcon from '../assets/images/boxing_king_icon_1789325912862.jpg';
import superElements2Icon from '../assets/images/super_elements_2_icon_1789326156273.jpg';
import wildBountyThumb from '../assets/images/wild_bounty_showdown_1789326457687.jpg';
import happyFishingIcon from '../assets/images/happy_fishing_icon_1789326697876.jpg';

interface WinnerItem {
  id: string;
  gameId: string;
  gameNameBn: string;
  gameNameEn: string;
  gameIcon: string;
  userPhone: string;
  amount: number;
  provider: string;
}

interface JackpotWinnersSectionProps {
  language: 'bn' | 'en';
  onLaunchGame?: (game: GameItem) => void;
}

const INITIAL_WINNERS: WinnerItem[] = [
  {
    id: 'w-1',
    gameId: 'game-super-ace',
    gameNameBn: 'সুপার এলিমেন্টস',
    gameNameEn: 'Super Elements',
    gameIcon: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******833',
    amount: 27640,
    provider: 'JILI'
  },
  {
    id: 'w-2',
    gameId: 'game-royal-fishing',
    gameNameBn: 'রয়্যাল ফিশিং',
    gameNameEn: 'Royal Fishing',
    gameIcon: 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******566',
    amount: 1125,
    provider: 'JILI'
  },
  {
    id: 'w-3',
    gameId: 'game-aviator',
    gameNameBn: 'এভিয়েটর',
    gameNameEn: 'Aviator',
    gameIcon: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******330',
    amount: 3260,
    provider: 'Spribe'
  },
  {
    id: 'w-4',
    gameId: 'game-aviator',
    gameNameBn: 'এভিয়েটর',
    gameNameEn: 'Aviator',
    gameIcon: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******075',
    amount: 4294,
    provider: 'Spribe'
  },
  {
    id: 'w-5',
    gameId: 'game-aviator',
    gameNameBn: 'এভিয়েটর',
    gameNameEn: 'Aviator',
    gameIcon: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******061',
    amount: 3192,
    provider: 'Spribe'
  },
  {
    id: 'w-6',
    gameId: 'game-fortune-gems-3',
    gameNameBn: 'ফরচুন জেমস ৩',
    gameNameEn: 'Fortune Gems 3',
    gameIcon: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******912',
    amount: 15420,
    provider: 'JILI'
  },
  {
    id: 'w-7',
    gameId: 'game-crazy-time',
    gameNameBn: 'ক্রেজি টাইম',
    gameNameEn: 'Crazy Time',
    gameIcon: 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?auto=format&fit=crop&w=120&q=80',
    userPhone: '*******448',
    amount: 58900,
    provider: 'Evolution'
  }
];

export const JackpotWinnersSection: React.FC<JackpotWinnersSectionProps> = ({
  language,
  onLaunchGame
}) => {
  const { favorites, toggleFavorite } = useFavorites();
  // Live increasing jackpot counter matching Capture.PNG (starting around ৳478,620,444.89)
  const [jackpotAmount, setJackpotAmount] = useState(478620444.89);
  const [winners, setWinners] = useState<WinnerItem[]>(INITIAL_WINNERS);

  // Live jackpot increments
  useEffect(() => {
    const timer = setInterval(() => {
      setJackpotAmount(prev => +(prev + (Math.random() * 2.85 + 0.45)).toFixed(2));
    }, 850);
    return () => clearInterval(timer);
  }, []);

  // Periodic new winner rolling animation
  useEffect(() => {
    const winnerInterval = setInterval(() => {
      const randomPhone = `*******${Math.floor(Math.random() * 900 + 100)}`;
      const randomGames = [
        { nameBn: 'সুপার এলিমেন্টস', nameEn: 'Super Elements', icon: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=120&q=80', id: 'game-super-ace', provider: 'JILI' },
        { nameBn: 'রয়্যাল ফিশিং', nameEn: 'Royal Fishing', icon: 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=120&q=80', id: 'game-royal-fishing', provider: 'JILI' },
        { nameBn: 'এভিয়েটর', nameEn: 'Aviator', icon: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=120&q=80', id: 'game-aviator', provider: 'Spribe' },
        { nameBn: 'ফরচুন জেমস ৩', nameEn: 'Fortune Gems 3', icon: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=120&q=80', id: 'game-fortune-gems-3', provider: 'JILI' },
        { nameBn: 'বক্সিং কিং', nameEn: 'Boxing King', icon: boxingKingIcon, id: 'game-boxing-king', provider: 'JILI' },
        { nameBn: 'সুপার এলিমেন্টস ২', nameEn: 'Super Elements 2', icon: superElements2Icon, id: 'game-super-elements-2', provider: 'FA CHAI' },
        { nameBn: 'ওয়াইল্ড বাউন্টি শোডাউন', nameEn: 'Wild Bounty Showdown', icon: wildBountyThumb, id: 'game-wild-bounty', provider: 'PG Soft' },
        { nameBn: 'হ্যাপি ফিশিং', nameEn: 'Happy Fishing', icon: happyFishingIcon, id: 'game-happy-fishing', provider: 'JILI' }
      ];
      const selected = randomGames[Math.floor(Math.random() * randomGames.length)];
      const randomWin = Math.floor(Math.random() * 35000) + 950;

      const newWinner: WinnerItem = {
        id: `w-${Date.now()}`,
        gameId: selected.id,
        gameNameBn: selected.nameBn,
        gameNameEn: selected.nameEn,
        gameIcon: selected.icon,
        userPhone: randomPhone,
        amount: randomWin,
        provider: selected.provider
      };

      setWinners(prev => [newWinner, ...prev.slice(0, 7)]);
    }, 4000);

    return () => clearInterval(winnerInterval);
  }, []);

  const formatJackpotNumber = (num: number) => {
    const parts = num.toFixed(2).split('.');
    const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return `${integerPart}.${parts[1]}`;
  };

  const handleLaunch = (winner: WinnerItem) => {
    if (!onLaunchGame) return;
    const existing = GAMES_DATA.find(g => g.id === winner.gameId);
    if (existing) {
      onLaunchGame(existing);
    } else {
      onLaunchGame({
        id: winner.gameId,
        name: winner.gameNameEn,
        nameBn: winner.gameNameBn,
        provider: winner.provider,
        category: 'hot',
        image: winner.gameIcon,
        isHot: true
      });
    }
  };

  return (
    <div 
      id="jackpot-winners-container"
      className="relative w-full rounded-3xl overflow-hidden bg-[#073c2a] border-2 border-[#196b4c] shadow-2xl transition-all duration-300 mt-4"
    >
      {/* Decorative Arch Frame Header mimicking Capture.PNG - Balanced & Clean */}
      <div className="relative min-h-[180px] sm:min-h-[210px] md:min-h-[240px] lg:min-h-[260px] pt-4 sm:pt-6 md:pt-8 pb-4 sm:pb-6 px-3 sm:px-8 bg-gradient-to-b from-[#0a4833] via-[#083c2a] to-[#073827] flex flex-col items-center justify-center overflow-hidden border-b border-[#12583d]">
        
        {/* Mint / White Stepped Arch Line Accent */}
        <div className="absolute top-2 inset-x-4 sm:inset-x-12 h-full pointer-events-none">
          <div className="w-full h-full border-t-2 border-x-2 border-[#52b788]/40 rounded-t-[32px] sm:rounded-t-[48px]" />
        </div>

        {/* Left Mascot (1st Character) - Smaller, Perfectly Proportioned */}
        <div className="absolute left-1 sm:left-3 md:left-6 lg:left-8 bottom-0 w-20 sm:w-28 md:w-36 lg:w-44 h-24 sm:h-32 md:h-44 lg:h-52 z-10 select-none flex items-end justify-start pointer-events-none">
          <div className="relative w-full h-full animate-bounce [animation-duration:3.5s] flex items-end justify-start">
            <img
              src="https://plain-apac-prod-public.komododecks.com/202608/29/oAFE0bcTgF0bABR0x5B6/image.png"
              alt="1st Mascot Character (Left)"
              referrerPolicy="no-referrer"
              className="w-full h-full object-contain object-bottom drop-shadow-[0_8px_16px_rgba(0,0,0,0.7)] filter brightness-105"
            />
          </div>
        </div>

        {/* Right Mascot (2nd Character) - Smaller, Perfectly Proportioned */}
        <div className="absolute right-1 sm:right-3 md:right-6 lg:right-8 bottom-0 w-20 sm:w-28 md:w-36 lg:w-44 h-24 sm:h-32 md:h-44 lg:h-52 z-10 select-none flex items-end justify-end pointer-events-none">
          <div className="relative w-full h-full animate-bounce [animation-duration:4s] flex items-end justify-end">
            <img
              src="https://plain-apac-prod-public.komododecks.com/202608/29/yLQ3OsrrCFrWIVIhR64E/image.png"
              alt="2nd Mascot Character (Right)"
              referrerPolicy="no-referrer"
              className="w-full h-full object-contain object-bottom drop-shadow-[0_8px_16px_rgba(0,0,0,0.7)] filter brightness-105"
            />
          </div>
        </div>

        {/* Center Illuminated JACKPOT Title Badge */}
        <div className="relative z-20 flex flex-col items-center">
          <div className="relative px-6 sm:px-10 py-1 sm:py-1.5 rounded-full bg-gradient-to-r from-[#ea580c] via-[#f97316] to-[#ea580c] border-2 border-yellow-300 shadow-[0_0_24px_rgba(234,88,12,0.85)] mb-1.5 flex items-center justify-center">
            {/* Glow bulbs effect */}
            <span className="jackpot-bulb-text text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-wider text-yellow-200">
              JACKPOT
            </span>
          </div>

          {/* Large Currency Ticker Amount ৳478,620,444.89 */}
          <div className="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-[#fef9c3] tracking-tight font-game drop-shadow-[0_4px_10px_rgba(0,0,0,0.85)] flex items-center gap-1 sm:gap-2">
            <span className="text-[#fef08a] font-normal text-2xl sm:text-4xl lg:text-5xl">৳</span>
            <span>{formatJackpotNumber(jackpotAmount)}</span>
          </div>
        </div>
      </div>

      {/* Live Winners Feed Table matching Capture.PNG */}
      <div className="w-full bg-[#063324] px-2 sm:px-6 py-2 space-y-1.5">
        {winners.slice(0, 5).map((winner, idx) => (
          <div
            key={winner.id}
            onClick={() => handleLaunch(winner)}
            className={`flex items-center justify-between py-2 sm:py-2.5 px-3 rounded-xl border border-[#0e4e37] bg-[#073c2a] hover:bg-[#0b4d37] transition-all duration-300 group cursor-pointer ${
              idx === 0 ? 'animate-in fade-in slide-in-from-top-3 duration-500 ring-1 ring-yellow-400/40' : ''
            }`}
          >
            {/* Left: Game Icon & Bengali Game Title */}
            <div className="flex items-center gap-2.5 min-w-[130px] sm:min-w-[180px]">
              <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-lg overflow-hidden shrink-0 border border-yellow-400/50 shadow-md bg-[#041a12]">
                <img
                  src={winner.gameIcon}
                  alt={winner.gameNameEn}
                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                />
              </div>
              <span className="text-xs sm:text-sm font-bold text-white group-hover:text-yellow-300 transition-colors truncate">
                {language === 'bn' ? winner.gameNameBn : winner.gameNameEn}
              </span>
            </div>

            {/* Middle: Masked Phone & Bengali 'একমাত্র জিতেছেন' */}
            <div className="text-center sm:text-left flex flex-col justify-center">
              <span className="text-[11px] sm:text-xs font-semibold text-emerald-200 tracking-wide">
                {winner.userPhone} {language === 'bn' ? 'একমাত্র' : 'just'}
              </span>
              <span className="text-[10px] sm:text-[11px] text-emerald-300 font-medium leading-none">
                {language === 'bn' ? 'জিতেছেন' : 'won'}
              </span>
            </div>

            {/* Right Middle: Win Amount (e.g. ৳27,640) */}
            <div className="text-right font-black text-xs sm:text-sm md:text-base text-yellow-300 font-game tracking-wide">
              ৳{winner.amount.toLocaleString('en-US')}
            </div>

            {/* Right: Favorite Toggle + Golden Rounded 'খেলুন' (Play) Button */}
            <div className="flex items-center gap-1.5 shrink-0">
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  toggleFavorite(winner.gameId, e);
                }}
                className="p-1.5 rounded-full bg-black/40 hover:bg-black/80 text-white transition-all hover:scale-110"
                title={favorites.includes(winner.gameId) ? 'Remove from favorites' : 'Add to favorites'}
              >
                <Heart className={`w-3.5 h-3.5 ${favorites.includes(winner.gameId) ? 'fill-rose-500 text-rose-500' : 'text-gray-400 hover:text-white'}`} />
              </button>

              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleLaunch(winner);
                }}
                className="px-3.5 sm:px-5 py-1 sm:py-1.5 rounded-full bg-gradient-to-b from-[#ffe566] via-[#facc15] to-[#eab308] hover:from-[#fff085] hover:to-[#facc15] text-[#3d2400] text-xs sm:text-sm font-extrabold shadow-md border border-amber-300 hover:scale-105 active:scale-95 transition-transform shrink-0 cursor-pointer"
              >
                {language === 'bn' ? 'খেলুন' : 'Play'}
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
