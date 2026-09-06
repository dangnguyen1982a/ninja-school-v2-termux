package server;

import assembly.Player;
import io.Message;
import java.io.ByteArrayOutputStream;

public class GameCanvas {

    public static void addInfoDlg(Session session, String s) {
        Message msg = null;
        try {
            msg = Service.messageNotMap((byte) (-86));
            msg.writer().writeUTF(s);
            msg.writer().flush();
            session.sendMessage(msg);
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void startOKDlg(Session session, String info) {
        Message msg = null;
        try {
            msg = new Message(-26);
            msg.writer().writeUTF(info);
            msg.writer().flush();
            session.sendMessage(msg);
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void addEffect(Session session, byte b, int vId, short id, int timelive, int miliSecondWait, boolean isHead) {
        Message msg = null;
        try {
            if (session != null) {
                msg = new Message(125);
                msg.writer().writeByte(0);
                msg.writer().writeByte(b);
                if (b == 1) {
                    msg.writer().writeByte(vId);
                } else {
                    msg.writer().writeInt(vId);
                }
                msg.writer().writeShort(id);
                msg.writer().writeInt(timelive);
                msg.writer().writeByte(miliSecondWait);
                msg.writer().writeByte(isHead ? 1 : 0);
                msg.writer().flush();
                session.sendMessage(msg);
            }
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void getImgEffect(Session session, short id) {
        Message msg = null;
        try {
            if (session != null) {
                //System.out.println("Lấy ảnh " + id);
                byte[] ab = GameSrc.loadFile("res/Effect/x" + session.zoomLevel + "/ImgEffect/ImgEffect " + id + ".png").toByteArray();
                if (ab != null) {
                    msg = new Message(125);
                    msg.writer().writeByte(1);
                    msg.writer().writeByte(id);
                    msg.writer().writeInt(ab.length);
                    msg.writer().write(ab);
                    msg.writer().flush();
                    session.sendMessage(msg);
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void getDataEffect(Session session, short id) {
        Message msg = null;
        try {
            if (session != null) {
                //System.out.println("Lấy data " + id);
                java.io.ByteArrayOutputStream data = GameSrc.loadFile("res/Effect/x" + session.zoomLevel + "/DataEffect/" + id); if (data == null) { System.out.println("THIEU EFFECT: " + id); return; } byte[] ab = data.toByteArray();
                if (ab != null) {
                    if (id == 21) {
                        ab[6] = 127;
                        ab[8] = 127;
                        ab[9] = 127;
                        ab[11] = 127;
                        ab[12] = 127;
                        ab[13] = 127;
                        ab[14] = 127;
                        ab[18] = 127;
                        ab[19] = 127;
                        ab[22] = 127;
                        ab[23] = 127;
                        ab[24] = 127;
                        ab[29] = -60;
                        ab[31] = 70;
                        ab[37] = -60;
                        ab[39] = 70;
                        ab[45] = -60;
                        ab[47] = 70;
                        ab[53] = -60;
                        ab[55] = 70;
                        ab[59] = 127;

                        msg = new Message(125);
                        msg.writer().write(ab);
                        msg.writer().flush();
                        session.sendMessage(msg);
                    } else {
                        msg = new Message(125);
                        msg.writer().write(ab);
                        msg.writer().flush();
                        session.sendMessage(msg);
                    }
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void sendImgAutoEffect(final Player p, int id) {
        Message msg = null;
        byte[] ab;
        try {
            ByteArrayOutputStream a = GameSrc.loadFile("res/EffectAuto/Img/" + p.conn.zoomLevel + "/" + id + ".png");
            if (a != null) {
                ab = a.toByteArray();
            } else {
                return;
            }
            msg = new Message((byte) (122));
            msg.writer().writeByte(2);
            msg.writer().writeByte(id);
            msg.writer().writeInt(ab.length);
            msg.writer().write(ab);
            p.conn.sendMessage(msg);
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void sendDataAutoEffect(final Player p, int id) {
        Message msg = null;
        byte[] ab;
        try {
            ByteArrayOutputStream a = GameSrc.loadFile("res/EffectAuto/Data/" + p.conn.zoomLevel + "/" + id);
            if (a != null) {
                ab = a.toByteArray();
            } else {
                return;
            }
            msg = new Message((byte) (122));
            msg.writer().writeByte(3);
            msg.writer().writeByte(id);
            msg.writer().writeShort(ab.length);
            msg.writer().write(ab);
            p.conn.sendMessage(msg);
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public static void SendAutoEffect(final Player p, byte id, int x, int y, byte count, short timeInSec) {
        Message msg = null;
        try {
            Message m = new Message(122);
            m.writer().writeByte(1);
            m.writer().writeByte(id);
            m.writer().writeShort(x);
            m.writer().writeShort(y);
            m.writer().writeByte(count);
            m.writer().writeShort(timeInSec);
            m.writer().flush();
            p.conn.sendMessage(m);
            m.cleanup();
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }
}
