/**
 * Social Media Accounts Configuration
 * Replace the href values with your actual links when ready.
 */
export interface SocialLink {
  id: string;
  name: string;
  nameAr: string;
  url: string;
  ariaLabel: string;
}

export const socialLinks: SocialLink[] = [
  {
    id: 'facebook',
    name: 'Facebook',
    nameAr: 'فيسبوك',
    url: '#', // Provide your Facebook page link here
    ariaLabel: 'صفحة منصة Codeera على فيسبوك'
  },
  {
    id: 'youtube',
    name: 'YouTube',
    nameAr: 'يوتيوب',
    url: '#', // Provide your YouTube channel link here
    ariaLabel: 'قناة منصة Codeera على يوتيوب'
  },
  {
    id: 'whatsapp',
    name: 'WhatsApp',
    nameAr: 'واتساب',
    url: '#', // Provide your WhatsApp link or group link here
    ariaLabel: 'تواصل معنا عبر واتساب'
  }
];
